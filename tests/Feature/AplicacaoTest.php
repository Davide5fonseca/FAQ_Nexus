<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Procedure;
use App\Models\SafetyRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AplicacaoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin Teste', 'email' => 'admin@teste.pt', 'password' => 'palavrapasse123']);
    }

    private function categoria(string $nome = 'Impressoras'): Category
    {
        return Category::create(['name' => $nome]);
    }

    /** Termina a sessão do teste, para os pedidos seguintes serem anónimos. */
    private function comoVisitante(): static
    {
        auth()->logout();
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function criarProcedimento(User $user, array $dados = []): Procedure
    {
        $cat = Category::firstOrCreate(['name' => 'Impressoras']);

        $this->actingAs($user)->post(route('admin.procedimentos.store'), array_merge([
            'title' => 'Substituir toner',
            'category_id' => $cat->id,
            'level' => 1,
            'steps' => ['Desligar a impressora', 'Abrir a tampa', 'Trocar o toner'],
            'ticket_notes' => 'Modelo e número de série',
            'escalation' => 'Se o erro persistir após troca',
        ], $dados))->assertRedirect(route('admin.procedimentos.index'));

        return Procedure::latest('id')->first();
    }

    // ---------------- Consulta pública ----------------

    public function test_consulta_e_publica_e_mostra_estado_vazio(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Ainda não há procedimentos.')
            ->assertSee('Entrar na administração');
    }

    public function test_consulta_mostra_procedimentos_e_regras(): void
    {
        $admin = $this->admin();
        SafetyRule::create(['position' => 1, 'content' => 'Desligar sempre da corrente.']);
        $p = $this->criarProcedimento($admin);

        $this->comoVisitante()->get('/')
            ->assertOk()
            ->assertSee('Regras de segurança')
            ->assertSee('Desligar sempre da corrente.')
            ->assertSee('PROC-01')
            ->assertSee('Substituir toner')
            ->assertSee('Trocar o toner')
            ->assertSee('Modelo e número de série')
            ->assertDontSee('Editar</a>', false);
    }

    public function test_pesquisa_e_filtros_na_consulta(): void
    {
        $admin = $this->admin();
        $redes = $this->categoria('Redes');
        $this->criarProcedimento($admin); // Impressoras, nível 1
        $this->criarProcedimento($admin, ['title' => 'Reiniciar router', 'category_id' => $redes->id, 'level' => 3, 'steps' => ['Desligar router 30 segundos']]);

        $this->comoVisitante();
        $this->get('/?q=router')->assertSee('Reiniciar router')->assertDontSee('Substituir toner');
        $this->get('/?q=toner')->assertSee('Substituir toner')->assertDontSee('Reiniciar router');
        $this->get('/?q=TAMPA')->assertSee('Substituir toner'); // pesquisa nos passos, sem distinguir maiúsculas
        $this->get('/?categoria='.$redes->id)->assertSee('Reiniciar router')->assertDontSee('Substituir toner');
        $this->get('/?nivel=1')->assertSee('Substituir toner')->assertDontSee('Reiniciar router');
        $this->get('/?q=inexistente')->assertSee('Nenhum procedimento corresponde aos filtros.');
        $this->get('/?q=100%')->assertOk(); // caracteres especiais do LIKE não rebentam
    }

    public function test_arquivados_nao_aparecem_na_consulta(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);

        $this->actingAs($admin)->post(route('admin.procedimentos.archive', $p))->assertRedirect();
        $this->comoVisitante()->get('/')->assertDontSee('Substituir toner');
        $this->get(route('imprimir.um', $p))->assertNotFound();

        $this->actingAs($admin)->post(route('admin.procedimentos.unarchive', $p))->assertRedirect();
        $this->comoVisitante()->get('/')->assertSee('Substituir toner');
    }

    public function test_paginas_de_impressao(): void
    {
        $admin = $this->admin();
        SafetyRule::create(['position' => 1, 'content' => 'Usar pulseira antiestática.']);
        $p = $this->criarProcedimento($admin);

        $this->comoVisitante()->get(route('imprimir'))->assertOk()->assertSee('Usar pulseira antiestática.')->assertSee('PROC-01')->assertSee('pag-imp');
        $this->get(route('imprimir.um', $p))->assertOk()->assertSee('Substituir toner')->assertDontSee('Usar pulseira antiestática.');
    }

    // ---------------- Autenticação ----------------

    public function test_administracao_exige_sessao(): void
    {
        $this->get('/admin/procedimentos')->assertRedirect(route('login'));
        $this->get('/admin/categorias')->assertRedirect(route('login'));
        $this->get('/admin/regras-seguranca')->assertRedirect(route('login'));
        $this->post('/admin/procedimentos', [])->assertRedirect(route('login'));
    }

    public function test_login_com_sucesso_e_logout(): void
    {
        $this->admin();

        $this->get(route('login'))->assertOk()->assertSee('Entrar na administração');

        $this->post(route('login.submit'), ['email' => 'admin@teste.pt', 'password' => 'palavrapasse123'])
            ->assertRedirect(route('admin.procedimentos.index'));
        $this->assertAuthenticated();

        $this->post(route('logout'))->assertRedirect(route('consulta'));
        $this->assertGuest();
    }

    public function test_login_errado_mostra_mensagem_em_portugues(): void
    {
        $this->admin();

        $this->from(route('login'))
            ->post(route('login.submit'), ['email' => 'admin@teste.pt', 'password' => 'errada'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Email ou palavra-passe incorrectos.']);
        $this->assertGuest();
    }

    public function test_login_bloqueia_apos_demasiadas_tentativas(): void
    {
        $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.submit'), ['email' => 'admin@teste.pt', 'password' => 'errada']);
        }
        $this->from(route('login'))
            ->post(route('login.submit'), ['email' => 'admin@teste.pt', 'password' => 'palavrapasse123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_palavra_passe_guardada_com_hash(): void
    {
        $user = $this->admin();
        $this->assertNotEquals('palavrapasse123', $user->password);
        $this->assertTrue(password_verify('palavrapasse123', $user->password));
    }

    // ---------------- Procedimentos ----------------

    public function test_criar_procedimento_gera_referencia_e_passos_ordenados(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin, ['steps' => ['Primeiro', '', '  ', 'Segundo']]);

        $this->assertSame('PROC-01', $p->reference);
        $this->assertSame(['Primeiro', 'Segundo'], $p->steps->pluck('content')->all());
        $this->assertSame('Admin Teste', $p->created_by);
        $this->assertSame('Admin Teste', $p->updated_by);

        $p2 = $this->criarProcedimento($admin);
        $this->assertSame('PROC-02', $p2->reference);
    }

    public function test_referencia_nao_e_reutilizada_depois_de_apagar(): void
    {
        $admin = $this->admin();
        $p1 = $this->criarProcedimento($admin);
        $p2 = $this->criarProcedimento($admin);
        $this->actingAs($admin)->delete(route('admin.procedimentos.destroy', $p2))->assertRedirect();
        $this->assertDatabaseMissing('procedures', ['id' => $p2->id]);

        $p3 = $this->criarProcedimento($admin);
        $this->assertSame('PROC-03', $p3->reference);
    }

    public function test_validacao_do_procedimento_em_portugues(): void
    {
        $admin = $this->admin();
        $this->categoria();

        $this->actingAs($admin)
            ->from(route('admin.procedimentos.create'))
            ->post(route('admin.procedimentos.store'), ['title' => '', 'category_id' => '', 'level' => 9, 'steps' => ['', '']])
            ->assertRedirect(route('admin.procedimentos.create'))
            ->assertSessionHasErrors([
                'title' => 'O campo título é obrigatório.',
                'category_id' => 'Escolha uma categoria.',
                'level' => 'O nível de intervenção tem de ser 1, 2 ou 3.',
                'steps' => 'Indique pelo menos um passo.',
            ]);
    }

    public function test_editar_procedimento_reordena_passos(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);

        $this->actingAs($admin)->put(route('admin.procedimentos.update', $p), [
            'title' => 'Substituir toner (rev.)',
            'category_id' => $p->category_id,
            'level' => 2,
            'steps' => ['Trocar o toner', 'Abrir a tampa'],
            'ticket_notes' => '',
            'escalation' => '',
        ])->assertRedirect(route('admin.procedimentos.index'));

        $p->refresh();
        $this->assertSame('Substituir toner (rev.)', $p->title);
        $this->assertSame(2, $p->level);
        $this->assertSame(['Trocar o toner', 'Abrir a tampa'], $p->steps->pluck('content')->all());
        $this->assertNull($p->ticket_notes);
    }

    public function test_duplicar_procedimento(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);

        $resp = $this->actingAs($admin)->post(route('admin.procedimentos.duplicate', $p));
        $copia = Procedure::latest('id')->first();

        $resp->assertRedirect(route('admin.procedimentos.edit', $copia));
        $this->assertSame('PROC-02', $copia->reference);
        $this->assertSame('Cópia de Substituir toner', $copia->title);
        $this->assertSame($p->steps->pluck('content')->all(), $copia->steps->pluck('content')->all());
    }

    public function test_lista_admin_mostra_arquivados_com_filtro(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);
        $this->actingAs($admin)->post(route('admin.procedimentos.archive', $p));

        $this->actingAs($admin)->get(route('admin.procedimentos.index'))->assertDontSee('Substituir toner');
        $this->actingAs($admin)->get(route('admin.procedimentos.index', ['estado' => 'arquivados']))->assertSee('Substituir toner')->assertSee('Arquivado');
        $this->actingAs($admin)->get(route('admin.procedimentos.index', ['estado' => 'todos']))->assertSee('Substituir toner');
    }

    public function test_formularios_admin_abrem(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.procedimentos.index'))->assertOk()->assertSee('Ainda não há procedimentos.');
        $this->actingAs($admin)->get(route('admin.procedimentos.create'))->assertOk()->assertSee('Ainda não existem categorias');
        $p = $this->criarProcedimento($admin);
        $this->actingAs($admin)->get(route('admin.procedimentos.edit', $p))->assertOk()->assertSee('PROC-01')->assertSee('Trocar o toner');
    }

    // ---------------- Categorias ----------------

    public function test_categorias_crud_e_proteccao_ao_apagar(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.categorias.index'))->assertOk()->assertSee('Ainda não há categorias.');

        $this->actingAs($admin)->post(route('admin.categorias.store'), ['name' => 'Portáteis'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.categorias.store'), ['name' => 'Portáteis'])
            ->assertSessionHasErrors(['name' => 'Já existe uma categoria com esse nome.']);

        $cat = Category::first();
        $this->actingAs($admin)->put(route('admin.categorias.update', $cat), ['name' => 'Computadores portáteis'])->assertRedirect();
        $this->assertSame('Computadores portáteis', $cat->fresh()->name);

        $this->criarProcedimento($admin, ['category_id' => $cat->id]);
        $this->actingAs($admin)->delete(route('admin.categorias.destroy', $cat))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);

        Procedure::query()->delete();
        $this->actingAs($admin)->delete(route('admin.categorias.destroy', $cat))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    // ---------------- Regras de segurança ----------------

    public function test_regras_crud_e_reordenacao(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.regras.index'))->assertOk()->assertSee('Ainda não há regras de segurança.');

        $this->actingAs($admin)->post(route('admin.regras.store'), ['content' => 'Regra A'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.regras.store'), ['content' => 'Regra B'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.regras.store'), ['content' => ''])->assertSessionHasErrors('content');

        [$a, $b] = SafetyRule::orderBy('position')->get();
        $this->assertSame([1, 2], [$a->position, $b->position]);

        $this->actingAs($admin)->post(route('admin.regras.move', $b), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['Regra B', 'Regra A'], SafetyRule::orderBy('position')->pluck('content')->all());

        $this->actingAs($admin)->put(route('admin.regras.update', $a), ['content' => 'Regra A editada'])->assertRedirect();
        $this->assertSame('Regra A editada', $a->fresh()->content);

        $this->actingAs($admin)->delete(route('admin.regras.destroy', $b))->assertRedirect();
        $this->assertSame([1], SafetyRule::pluck('position')->all());
    }

    // ---------------- Comandos ----------------

    public function test_comandos_de_administrador(): void
    {
        $this->artisan('app:criar-admin', ['--nome' => 'Chefe', '--email' => 'chefe@nxs.pt', '--password' => 'segredo-muito-longo'])
            ->assertSuccessful();
        $this->assertDatabaseHas('users', ['email' => 'chefe@nxs.pt']);

        $this->artisan('app:criar-admin', ['--nome' => 'X', '--email' => 'x@nxs.pt', '--password' => 'curta'])
            ->assertFailed();

        $this->artisan('app:alterar-password', ['--password' => 'nova-palavra-passe-1'])->assertSuccessful();
        $this->assertTrue(password_verify('nova-palavra-passe-1', User::first()->password));
    }
}
