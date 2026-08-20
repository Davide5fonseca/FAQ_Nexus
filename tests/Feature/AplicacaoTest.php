<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Procedure;
use App\Models\SafetyRule;
use App\Models\User;
use App\Notifications\DefinirPalavraPasse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AplicacaoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin Teste', 'email' => 'admin@teste.pt', 'password' => 'palavrapasse123', 'role' => 'admin', 'area' => 'tecnica', 'active' => true]);
    }

    private function editor(string $area = 'producao'): User
    {
        return User::create(['name' => 'Editor Teste', 'email' => 'editor@teste.pt', 'password' => 'palavrapasse123', 'role' => 'editor', 'area' => $area, 'active' => true]);
    }

    private function categoria(string $nome = 'Impressoras'): Category
    {
        return Category::create(['name' => $nome]);
    }

    private function leitor(string $area = 'tecnica'): User
    {
        return User::create(['name' => 'Leitor Teste', 'email' => 'leitor@teste.pt', 'password' => 'palavrapasse123', 'role' => 'leitor', 'area' => $area, 'active' => true]);
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
            'steps' => ['Desligar a impressora', 'Abrir a tampa', 'Trocar o toner'],
            'ticket_notes' => 'Modelo e número de série',
            'escalation' => 'Se o erro persistir após troca',
        ], $dados))->assertRedirect(route('admin.procedimentos.index'));

        return Procedure::latest('id')->first();
    }

    // ---------------- Consulta pública ----------------

    public function test_consulta_exige_sessao(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('imprimir'))->assertRedirect(route('login'));

        $this->actingAs($this->admin())->get('/')
            ->assertOk()
            ->assertSee('Ainda não há procedimentos.');
    }

    public function test_consulta_mostra_procedimentos_e_regras(): void
    {
        $admin = $this->admin();
        SafetyRule::create(['position' => 1, 'content' => 'Desligar sempre da corrente.']);
        $p = $this->criarProcedimento($admin);

        $this->get('/')
            ->assertOk()
            ->assertSee('Regras de segurança')
            ->assertSee('Desligar sempre da corrente.')
            ->assertSee('PROC-01')
            ->assertSee('Substituir toner')
            ->assertSee('Trocar o toner')
            ->assertSee('Modelo e número de série');
    }

    public function test_pesquisa_e_filtros_na_consulta(): void
    {
        $admin = $this->admin();
        $redes = $this->categoria('Redes');
        $this->criarProcedimento($admin); // Impressoras, nível 1
        $this->criarProcedimento($admin, ['title' => 'Reiniciar router', 'category_id' => $redes->id, 'steps' => ['Desligar router 30 segundos']]);

        $this->get('/?q=router')->assertSee('Reiniciar router')->assertDontSee('Substituir toner');
        $this->get('/?q=toner')->assertSee('Substituir toner')->assertDontSee('Reiniciar router');
        $this->get('/?q=TAMPA')->assertSee('Substituir toner'); // pesquisa nos passos, sem distinguir maiúsculas
        $this->get('/?categoria='.$redes->id)->assertSee('Reiniciar router')->assertDontSee('Substituir toner');
        $this->get('/?q=inexistente')->assertSee('Nenhum procedimento corresponde aos filtros.');
        $this->get('/?q=100%')->assertOk(); // caracteres especiais do LIKE não rebentam
    }

    public function test_eliminar_remove_da_consulta(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);
        $this->actingAs($admin)->get('/')->assertSee('Substituir toner');

        $this->actingAs($admin)->delete(route('admin.procedimentos.destroy', $p))->assertRedirect();

        $this->actingAs($admin)->get('/')->assertDontSee('Substituir toner');
        $this->assertDatabaseMissing('procedures', ['id' => $p->id]);
    }

    public function test_nao_existe_nenhuma_forma_de_arquivar(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);

        // Nem interface nem endereços: o conceito deixou de existir
        $this->actingAs($admin)->get(route('admin.procedimentos.index'))
            ->assertDontSee('Arquivar')
            ->assertDontSee('Arquivado');
        $this->actingAs($admin)->get(route('admin.procedimentos.edit', $p))
            ->assertDontSee('Arquivar')
            ->assertDontSee('Desarquivar');

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.procedimentos.archive'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.procedimentos.unarchive'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('procedures', 'archived_at'));
    }

    public function test_paginas_de_impressao(): void
    {
        $admin = $this->admin();
        SafetyRule::create(['position' => 1, 'content' => 'Usar pulseira antiestática.']);
        $p = $this->criarProcedimento($admin);

        $this->get(route('imprimir'))->assertOk()->assertSee('Usar pulseira antiestática.')->assertSee('PROC-01')->assertSee('pag-imp');
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

        $this->get(route('login'))->assertOk()->assertSee('Esqueci-me da palavra-passe');

        $this->post(route('login.submit'), ['email' => 'admin@teste.pt', 'password' => 'palavrapasse123'])
            ->assertRedirect(route('consulta'));
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
            ->assertSessionHasErrors(['email' => 'Email ou palavra-passe incorrectos, ou conta desactivada.']);
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
        $this->assertSame('Admin Teste (Área técnica)', $p->created_by);
        $this->assertSame('Admin Teste (Área técnica)', $p->updated_by);

        $p2 = $this->criarProcedimento($admin);
        $this->assertSame('PROC-02', $p2->reference);
    }

    public function test_referencia_nao_e_reutilizada_depois_de_eliminar(): void
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
            ->post(route('admin.procedimentos.store'), ['title' => '', 'category_id' => '', 'steps' => ['', '']])
            ->assertRedirect(route('admin.procedimentos.create'))
            ->assertSessionHasErrors([
                'title' => 'O campo título é obrigatório.',
                'category_id' => 'Escolha uma categoria.',
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
            'steps' => ['Trocar o toner', 'Abrir a tampa'],
            'ticket_notes' => '',
            'escalation' => '',
        ])->assertRedirect(route('admin.procedimentos.index'));

        $p->refresh();
        $this->assertSame('Substituir toner (rev.)', $p->title);
        $this->assertSame(['Trocar o toner', 'Abrir a tampa'], $p->steps->pluck('content')->all());
        $this->assertNull($p->ticket_notes);
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

    public function test_categorias_crud_e_proteccao_ao_eliminar(): void
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

    // ---------------- Perfis: editor vs administrador ----------------

    public function test_editor_cria_e_edita_mas_nao_gere_nem_elimina(): void
    {
        $this->categoria();
        $editor = $this->editor('producao');


        $p = $this->criarProcedimento($editor, ['title' => 'Máquina pára a meio', 'problem' => 'A máquina pára e acende luz vermelha']);
        $this->assertSame('Editor Teste (Produção)', $p->created_by);
        $this->assertSame('A máquina pára e acende luz vermelha', $p->problem);

        $this->actingAs($editor)->get(route('admin.procedimentos.index'))->assertOk()->assertDontSee('>Eliminar<', false)->assertDontSee('Utilizadores');
        $this->actingAs($editor)->get(route('admin.procedimentos.edit', $p))->assertOk();

        $this->actingAs($editor)->delete(route('admin.procedimentos.destroy', $p))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.categorias.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.regras.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.utilizadores.index'))->assertForbidden();
        $this->actingAs($editor)->post(route('admin.utilizadores.store'), [])->assertForbidden();
    }

    public function test_problema_aparece_na_consulta_e_na_pesquisa(): void
    {
        $admin = $this->admin();
        $this->criarProcedimento($admin, ['problem' => 'Luz vermelha intermitente no painel']);

        $this->get('/')->assertSee('Problema / sintomas')->assertSee('Luz vermelha intermitente no painel');
        $this->get('/?q=intermitente')->assertSee('Substituir toner');
        $this->get(route('imprimir'))->assertSee('Luz vermelha intermitente no painel');
    }

    public function test_gestao_de_utilizadores(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.utilizadores.index'))->assertOk()->assertSee('Admin Teste');
        $this->actingAs($admin)->get(route('admin.utilizadores.create'))->assertOk();

        Notification::fake();

        $this->actingAs($admin)->post(route('admin.utilizadores.store'), [
            'name' => 'Rita Produção', 'email' => 'rita@teste.pt', 'role' => 'editor', 'area' => 'producao',
        ])->assertRedirect(route('admin.utilizadores.index'));
        $rita = User::where('email', 'rita@teste.pt')->first();
        $this->assertSame('editor', $rita->role);
        $this->assertSame('producao', $rita->area);
        Notification::assertSentTo($rita, DefinirPalavraPasse::class);

        // validação
        $this->actingAs($admin)->post(route('admin.utilizadores.store'), ['name' => '', 'email' => 'rita@teste.pt', 'role' => 'x', 'area' => ''])
            ->assertSessionHasErrors(['name', 'email' => 'Já existe uma conta com esse email.', 'role', 'area']);

        // editar sem mudar palavra-passe, desactivar
        $this->actingAs($admin)->put(route('admin.utilizadores.update', $rita), [
            'name' => 'Rita P.', 'email' => 'rita@teste.pt', 'role' => 'editor', 'area' => 'producao', 'password' => '', 'active' => '0',
        ])->assertRedirect();
        $rita->refresh();
        $this->assertSame('Rita P.', $rita->name);
        $this->assertFalse($rita->active);

        // conta desactivada não entra
        $this->comoVisitante()->post(route('login.submit'), ['email' => 'rita@teste.pt', 'password' => 'palavrapasse123'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // protecções: não se apaga a si próprio nem o último admin
        $this->actingAs($admin)->delete(route('admin.utilizadores.destroy', $admin))->assertSessionHasErrors('user');
        $this->actingAs($admin)->put(route('admin.utilizadores.update', $admin), [
            'name' => 'Admin Teste', 'email' => 'admin@teste.pt', 'role' => 'editor', 'area' => 'tecnica', 'password' => '', 'active' => '1',
        ])->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)->delete(route('admin.utilizadores.destroy', $rita))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $rita->id]);
    }

    public function test_leitor_so_consulta_e_nao_entra_na_administracao(): void
    {
        $admin = $this->admin();
        $p = $this->criarProcedimento($admin);
        $leitor = $this->leitor();

        // Consulta e impressão: pode
        $this->actingAs($leitor)->get('/')
            ->assertOk()
            ->assertSee('Substituir toner')
            ->assertDontSee('Novo procedimento')
            ->assertDontSee('>Editar</a>', false)
            ->assertDontSee('Administração');
        $this->actingAs($leitor)->get(route('imprimir'))->assertOk();
        $this->actingAs($leitor)->get(route('imprimir.um', $p))->assertOk();

        // Administração: nada
        foreach ([
            route('admin.procedimentos.index'),
            route('admin.procedimentos.create'),
            route('admin.procedimentos.edit', $p),
            route('admin.categorias.index'),
            route('admin.regras.index'),
            route('admin.utilizadores.index'),
        ] as $url) {
            $this->actingAs($leitor)->get($url)->assertForbidden();
        }

        // E também não consegue gravar nada
        $this->actingAs($leitor)->post(route('admin.procedimentos.store'), [
            'title' => 'Tentativa', 'category_id' => $p->category_id, 'steps' => ['x'],
        ])->assertForbidden();
        $this->actingAs($leitor)->delete(route('admin.procedimentos.destroy', $p))->assertForbidden();
        $this->assertDatabaseHas('procedures', ['id' => $p->id]);
    }

    public function test_admin_pode_criar_conta_de_leitor(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.utilizadores.store'), [
            'name' => 'Ana Leitora', 'email' => 'ana@teste.pt', 'role' => 'leitor', 'area' => 'producao',
        ])->assertRedirect(route('admin.utilizadores.index'));

        $ana = User::where('email', 'ana@teste.pt')->first();
        $this->assertSame('leitor', $ana->role);
        $this->assertFalse($ana->pode_editar);
        $this->assertSame('Leitor', $ana->role_label);
        Notification::assertSentTo($ana, DefinirPalavraPasse::class);
    }

    // ---------------- Separação por área ----------------

    public function test_cada_area_so_ve_o_seu_conteudo(): void
    {
        $admin = $this->admin();                       // área técnica
        $tecnico = $this->criarProcedimento($admin);   // fica na área técnica

        // Procedimento da produção, criado por um editor dessa área
        $editorProd = $this->editor('producao');
        $prod = $this->criarProcedimento($editorProd, ['title' => 'Máquina de embalar encrava']);
        $this->assertSame('producao', $prod->area);
        $this->assertSame('tecnica', $tecnico->area);

        // Técnico: vê o seu, não vê o da produção
        $tec = $this->leitor('tecnica');
        $this->actingAs($tec)->get('/')
            ->assertSee('Substituir toner')
            ->assertDontSee('Máquina de embalar encrava');

        // Produção: o contrário
        $this->actingAs($editorProd)->get('/')
            ->assertSee('Máquina de embalar encrava')
            ->assertDontSee('Substituir toner');

        // Administrador vê tudo
        $this->actingAs($admin)->get('/')
            ->assertSee('Substituir toner')
            ->assertSee('Máquina de embalar encrava');
    }

    public function test_nao_se_acede_a_procedimento_de_outra_area(): void
    {
        $admin = $this->admin();
        $tecnico = $this->criarProcedimento($admin);   // área técnica
        $editorProd = $this->editor('producao');

        // Nem para ver/imprimir, nem para editar ou apagar
        $this->actingAs($editorProd)->get(route('imprimir.um', $tecnico))->assertForbidden();
        $this->actingAs($editorProd)->get(route('admin.procedimentos.edit', $tecnico))->assertForbidden();
        $this->actingAs($editorProd)->put(route('admin.procedimentos.update', $tecnico), [
            'title' => 'Alterado à força', 'category_id' => $tecnico->category_id, 'steps' => ['x'],
        ])->assertForbidden();

        $this->assertSame('Substituir toner', $tecnico->fresh()->title);

        // Na lista de administração também não aparece
        $this->actingAs($editorProd)->get(route('admin.procedimentos.index'))
            ->assertDontSee('Substituir toner');
    }

    public function test_administrador_escolhe_a_area_do_procedimento(): void
    {
        $admin = $this->admin();                    // área técnica
        $cat = $this->categoria();

        // Cria conteúdo para a produção, apesar de ser da área técnica
        $this->actingAs($admin)->post(route('admin.procedimentos.store'), [
            'title' => 'Instrução da linha 3', 'category_id' => $cat->id,
            'area' => 'producao', 'steps' => ['Passo um'],
        ])->assertRedirect(route('admin.procedimentos.index'));

        $p = Procedure::latest('id')->first();
        $this->assertSame('producao', $p->area);

        // A produção vê-o; a área técnica não
        $this->actingAs($this->editor('producao'))->get('/')->assertSee('Instrução da linha 3');
        $this->actingAs($this->leitor('tecnica'))->get('/')->assertDontSee('Instrução da linha 3');
    }

    public function test_categorias_da_consulta_so_mostram_as_da_propria_area(): void
    {
        $admin = $this->admin();
        $this->criarProcedimento($admin); // categoria "Impressoras", área técnica

        $linha = Category::create(['name' => 'Linha de montagem']);
        $editorProd = $this->editor('producao');
        $this->criarProcedimento($editorProd, ['title' => 'Ajustar tapete', 'category_id' => $linha->id]);

        $this->actingAs($editorProd)->get('/')
            ->assertSee('Linha de montagem')
            ->assertDontSee('Impressoras');
    }

    // ---------------- Convite e recuperação de palavra-passe ----------------

    public function test_convite_por_email_e_definicao_de_palavra_passe(): void
    {
        Notification::fake();
        $admin = $this->admin();

        // Admin cria a conta só com os dados (sem palavra-passe)
        $this->actingAs($admin)->post(route('admin.utilizadores.store'), [
            'name' => 'Rui Técnico', 'email' => 'rui@teste.pt', 'role' => 'editor', 'area' => 'tecnica',
        ])->assertRedirect(route('admin.utilizadores.index'));

        $rui = User::where('email', 'rui@teste.pt')->first();
        Notification::assertSentTo($rui, DefinirPalavraPasse::class);

        // Reenviar convite
        $this->actingAs($admin)->post(route('admin.utilizadores.convite', $rui))->assertRedirect();
        Notification::assertSentToTimes($rui, DefinirPalavraPasse::class, 2);

        // A pessoa abre o link e define a palavra-passe
        $token = Password::createToken($rui);
        $this->comoVisitante();
        $this->get(route('password.reset', ['token' => $token, 'email' => 'rui@teste.pt']))
            ->assertOk()->assertSee('Definir palavra-passe');
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'rui@teste.pt',
            'password' => 'novapalavra123', 'password_confirmation' => 'novapalavra123',
        ])->assertRedirect(route('login'));

        // E entra com ela
        $this->post(route('login.submit'), ['email' => 'rui@teste.pt', 'password' => 'novapalavra123'])
            ->assertRedirect(route('consulta'));
        $this->assertAuthenticated();
    }

    public function test_token_invalido_nao_define_palavra_passe(): void
    {
        Notification::fake();
        $user = $this->admin();

        $this->comoVisitante();
        $this->post(route('password.update'), [
            'token' => 'token-errado', 'email' => $user->email,
            'password' => 'novapalavra123', 'password_confirmation' => 'novapalavra123',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.submit'), ['email' => $user->email, 'password' => 'novapalavra123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_recuperacao_de_palavra_passe(): void
    {
        Notification::fake();
        $user = $this->admin();

        $this->get(route('password.request'))->assertOk()->assertSee('Recuperar palavra-passe');
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);

        // Email inexistente: resposta igual, sem revelar se a conta existe
        $this->post(route('password.email'), ['email' => 'ninguem@teste.pt'])
            ->assertRedirect()->assertSessionHas('status');
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
