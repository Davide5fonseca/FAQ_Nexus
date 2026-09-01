<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Perfil;
use App\Models\Procedure;
use App\Models\SafetyRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AplicacaoTest extends TestCase
{
    use RefreshDatabase;

    private int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Esta aplicação registada no portal, como acontece em produção.
        DB::connection(config('database.suite'))->table('aplicacoes')->insert([
            'id' => 1, 'chave' => config('app.chave'), 'nome' => 'Knowledgebase',
            'url' => 'https://exemplo.pt/kb', 'activa' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Uma pessoa com conta no portal, com acesso a esta aplicação e com um
     * perfil aqui dentro. É assim que as coisas se passam em produção.
     */
    private function pessoa(string $papel = 'admin', string $area = 'tecnica', bool $comAcesso = true): User
    {
        $this->contador++;

        // forceCreate: esta aplicação não cria contas (é o portal que o faz),
        // por isso o modelo não permite criação em massa.
        $user = User::forceCreate([
            'nome' => "Pessoa {$this->contador}",
            'email' => "pessoa{$this->contador}@teste.pt",
            'password' => Hash::make('palavrapasse123'),
            'papel' => 'tecnico',
            'ativo' => true,
        ]);

        if ($comAcesso) {
            DB::connection(config('database.suite'))->table('acessos')->insert([
                'utilizador_id' => $user->id, 'aplicacao_id' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Perfil::create(['utilizador_id' => $user->id, 'papel' => $papel, 'area' => $area]);

        return $user->fresh();
    }

    private function categoria(string $nome = 'Impressoras'): Category
    {
        return Category::firstOrCreate(['name' => $nome]);
    }

    private function criarProcedimento(User $user, array $dados = []): Procedure
    {
        $cat = $this->categoria();

        $this->actingAs($user)->post(route('gerir.procedimentos.store'), array_merge([
            'title' => 'Substituir toner',
            'category_id' => $cat->id,
            'steps' => ['Desligar a impressora', 'Abrir a tampa', 'Trocar o toner'],
            'ticket_notes' => 'Modelo e número de série',
            'escalation' => 'Se o erro persistir após troca',
        ], $dados))->assertRedirect(route('gerir.procedimentos.index'));

        return Procedure::latest('id')->first();
    }

    // ---------------- Entrada e acesso ----------------

    public function test_sem_sessao_vai_ao_portal(): void
    {
        $this->get('/')->assertRedirect(config('app.portal_url'));
        $this->get(route('gerir.procedimentos.index'))->assertRedirect(config('app.portal_url'));
    }

    public function test_sem_acesso_atribuido_no_portal_e_devolvido_ao_portal(): void
    {
        $semAcesso = $this->pessoa(comAcesso: false);

        $this->actingAs($semAcesso)->get('/')->assertRedirect(config('app.portal_url'));
        $this->actingAs($semAcesso)->get(route('gerir.procedimentos.index'))
            ->assertRedirect(config('app.portal_url'));
    }

    public function test_com_acesso_entra(): void
    {
        $this->actingAs($this->pessoa())->get('/')->assertOk();
    }

    public function test_conta_desactivada_no_portal_perde_a_sessao(): void
    {
        $user = $this->pessoa();
        $this->actingAs($user)->get('/')->assertOk();

        $user->forceFill(['ativo' => false])->save();
        $this->app['auth']->forgetGuards();

        $this->actingAs($user->fresh())->get('/')->assertRedirect(config('app.portal_url'));
    }

    // ---------------- Separação por área ----------------

    public function test_cada_area_so_ve_o_seu_conteudo(): void
    {
        $tecnico = $this->pessoa('admin', 'tecnica');
        $this->criarProcedimento($tecnico);

        $producao = $this->pessoa('editor', 'producao');
        $this->criarProcedimento($producao, ['title' => 'Máquina de embalar encrava']);

        $this->assertSame('tecnica', Procedure::where('title', 'Substituir toner')->first()->area);
        $this->assertSame('producao', Procedure::where('title', 'Máquina de embalar encrava')->first()->area);

        // Cada um vê o seu e não vê o do outro
        $this->actingAs($this->pessoa('leitor', 'tecnica'))->get('/')
            ->assertSee('Substituir toner')->assertDontSee('Máquina de embalar encrava');

        $this->actingAs($this->pessoa('leitor', 'producao'))->get('/')
            ->assertSee('Máquina de embalar encrava')->assertDontSee('Substituir toner');
    }

    public function test_nao_se_acede_a_procedimento_de_outra_area(): void
    {
        $tecnico = $this->pessoa('admin', 'tecnica');
        $p = $this->criarProcedimento($tecnico);

        // Editor: os administradores vêem todas as áreas, de propósito.
        $producao = $this->pessoa('editor', 'producao');

        $this->actingAs($producao)->get(route('imprimir.um', $p))->assertForbidden();
        $this->actingAs($producao)->get(route('gerir.procedimentos.edit', $p))->assertForbidden();
        $this->actingAs($producao)->put(route('gerir.procedimentos.update', $p), [
            'title' => 'Alterado à força', 'category_id' => $p->category_id, 'steps' => ['x'],
        ])->assertForbidden();

        $this->assertSame('Substituir toner', $p->fresh()->title);
        $this->actingAs($producao)->get(route('gerir.procedimentos.index'))->assertDontSee('Substituir toner');
    }

    public function test_administrador_ve_todas_as_areas(): void
    {
        $tecnico = $this->pessoa('editor', 'tecnica');
        $this->criarProcedimento($tecnico);

        $producao = $this->pessoa('editor', 'producao');
        $this->criarProcedimento($producao, ['title' => 'Máquina de embalar encrava']);

        $this->actingAs($this->pessoa('admin', 'tecnica'))->get('/')
            ->assertSee('Substituir toner')
            ->assertSee('Máquina de embalar encrava');
    }

    public function test_categorias_da_consulta_so_mostram_as_da_propria_area(): void
    {
        $tecnico = $this->pessoa('admin', 'tecnica');
        $this->criarProcedimento($tecnico); // categoria "Impressoras"

        $linha = Category::create(['name' => 'Linha de montagem']);
        $producao = $this->pessoa('editor', 'producao');
        $this->criarProcedimento($producao, ['title' => 'Ajustar tapete', 'category_id' => $linha->id]);

        $this->actingAs($producao)->get('/')
            ->assertSee('Linha de montagem')->assertDontSee('Impressoras');
    }

    // ---------------- Perfis ----------------

    public function test_leitor_so_consulta(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');
        $p = $this->criarProcedimento($admin);
        $leitor = $this->pessoa('leitor', 'tecnica');

        $this->actingAs($leitor)->get('/')->assertOk()->assertSee('Substituir toner')
            ->assertDontSee('Novo procedimento')->assertDontSee('>Editar</a>', false);
        $this->actingAs($leitor)->get(route('imprimir.um', $p))->assertOk();

        foreach ([
            route('gerir.procedimentos.index'),
            route('gerir.procedimentos.create'),
            route('gerir.categorias.index'),
            route('gerir.utilizadores.index'),
        ] as $url) {
            $this->actingAs($leitor)->get($url)->assertForbidden();
        }

        $this->actingAs($leitor)->post(route('gerir.procedimentos.store'), [
            'title' => 'Tentativa', 'category_id' => $p->category_id, 'steps' => ['x'],
        ])->assertForbidden();
    }

    public function test_editor_cria_e_edita_mas_nao_gere_nem_elimina(): void
    {
        $editor = $this->pessoa('editor', 'producao');
        $p = $this->criarProcedimento($editor, ['title' => 'Máquina pára a meio']);

        $this->assertSame('Pessoa 1 (Produção)', $p->created_by);

        $this->actingAs($editor)->get(route('gerir.procedimentos.index'))->assertOk()
            ->assertDontSee('>Eliminar<', false);
        $this->actingAs($editor)->get(route('gerir.procedimentos.edit', $p))->assertOk();

        $this->actingAs($editor)->delete(route('gerir.procedimentos.destroy', $p))->assertForbidden();
        $this->actingAs($editor)->get(route('gerir.categorias.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('gerir.regras.index'))->assertForbidden();
        $this->actingAs($editor)->get(route('gerir.utilizadores.index'))->assertForbidden();
    }

    public function test_sem_perfil_definido_e_apenas_leitor(): void
    {
        $user = $this->pessoa();
        Perfil::where('utilizador_id', $user->id)->delete();
        $user = $user->fresh();

        $this->assertSame('leitor', $user->role);
        $this->assertFalse($user->pode_editar);
        $this->actingAs($user)->get(route('gerir.procedimentos.index'))->assertForbidden();
    }

    public function test_sem_area_a_consulta_explica_em_vez_de_dizer_que_nao_ha_nada(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');
        $this->criarProcedimento($admin, ['title' => 'Substituir toner']);

        $semArea = $this->pessoa('leitor', 'tecnica');
        Perfil::where('utilizador_id', $semArea->id)->delete();

        // Há procedimentos; o que falta é a área. Dizer que não há conteúdo
        // manda esta pessoa procurar um problema que não é dela.
        $this->actingAs($semArea->fresh())->get(route('consulta'))
            ->assertOk()
            ->assertSee('A sua conta ainda não tem área.')
            ->assertDontSee('Ainda não há procedimentos.');
    }

    public function test_quem_tem_area_continua_a_ver_os_procedimentos(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');
        $this->criarProcedimento($admin, ['title' => 'Substituir toner']);

        $this->actingAs($this->pessoa('leitor', 'tecnica'))->get(route('consulta'))
            ->assertOk()
            ->assertSee('Substituir toner')
            ->assertDontSee('A sua conta ainda não tem área.');
    }

    public function test_a_gestao_assinala_quem_ficou_sem_area(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');
        $sem = $this->pessoa('leitor', 'tecnica');
        Perfil::where('utilizador_id', $sem->id)->delete();

        $this->actingAs($admin)->get(route('gerir.utilizadores.index'))
            ->assertOk()
            ->assertSee('Por definir');
    }

    // ---------------- Gestão de perfis ----------------

    public function test_administrador_altera_o_perfil_de_alguem(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');
        $outro = $this->pessoa('leitor', 'tecnica');

        $this->actingAs($admin)->get(route('gerir.utilizadores.index'))->assertOk()
            ->assertSee($outro->name);

        $this->actingAs($admin)->put(route('gerir.utilizadores.update', $outro->id), [
            'papel' => 'editor', 'area' => 'producao',
        ])->assertRedirect(route('gerir.utilizadores.index'));

        $this->assertDatabaseHas('perfis', [
            'utilizador_id' => $outro->id, 'papel' => 'editor', 'area' => 'producao',
        ]);
    }

    public function test_nao_se_fica_sem_administrador(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');

        $this->actingAs($admin)->put(route('gerir.utilizadores.update', $admin->id), [
            'papel' => 'leitor', 'area' => 'tecnica',
        ])->assertSessionHasErrors('papel');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_quem_nao_tem_acesso_nao_aparece_na_gestao(): void
    {
        $admin = $this->pessoa('admin', 'tecnica');
        $forasteiro = $this->pessoa('editor', 'tecnica', comAcesso: false);

        $this->actingAs($admin)->get(route('gerir.utilizadores.index'))
            ->assertDontSee($forasteiro->email);
        $this->actingAs($admin)->get(route('gerir.utilizadores.edit', $forasteiro->id))
            ->assertNotFound();
    }

    // ---------------- Procedimentos ----------------

    public function test_criar_gera_referencia_e_passos_ordenados(): void
    {
        $admin = $this->pessoa();
        $p = $this->criarProcedimento($admin, ['steps' => ['Primeiro', '', '  ', 'Segundo']]);

        $this->assertSame('PROC-01', $p->reference);
        $this->assertSame(['Primeiro', 'Segundo'], $p->steps->pluck('content')->all());
        $this->assertSame('Pessoa 1 (Área técnica)', $p->created_by);

        $this->assertSame('PROC-02', $this->criarProcedimento($admin)->reference);
    }

    public function test_referencia_nao_e_reutilizada_depois_de_eliminar(): void
    {
        $admin = $this->pessoa();
        $this->criarProcedimento($admin);
        $p2 = $this->criarProcedimento($admin);

        $this->actingAs($admin)->delete(route('gerir.procedimentos.destroy', $p2))->assertRedirect();
        $this->assertSame('PROC-03', $this->criarProcedimento($admin)->reference);
    }

    public function test_validacao_em_portugues(): void
    {
        $admin = $this->pessoa();
        $this->categoria();

        $this->actingAs($admin)->from(route('gerir.procedimentos.create'))
            ->post(route('gerir.procedimentos.store'), ['title' => '', 'category_id' => '', 'steps' => ['', '']])
            ->assertSessionHasErrors([
                'title' => 'O campo título é obrigatório.',
                'category_id' => 'Escolha uma categoria.',
                'steps' => 'Indique pelo menos um passo.',
            ]);
    }

    public function test_editar_reordena_passos(): void
    {
        $admin = $this->pessoa();
        $p = $this->criarProcedimento($admin);

        $this->actingAs($admin)->put(route('gerir.procedimentos.update', $p), [
            'title' => 'Substituir toner (rev.)',
            'category_id' => $p->category_id,
            'steps' => ['Trocar o toner', 'Abrir a tampa'],
            'ticket_notes' => '', 'escalation' => '',
        ])->assertRedirect(route('gerir.procedimentos.index'));

        $p->refresh();
        $this->assertSame('Substituir toner (rev.)', $p->title);
        $this->assertSame(['Trocar o toner', 'Abrir a tampa'], $p->steps->pluck('content')->all());
    }

    public function test_eliminar_remove_da_consulta(): void
    {
        $admin = $this->pessoa();
        $p = $this->criarProcedimento($admin);

        $this->actingAs($admin)->delete(route('gerir.procedimentos.destroy', $p))->assertRedirect();
        $this->actingAs($admin)->get('/')->assertDontSee('Substituir toner');
        $this->assertDatabaseMissing('procedures', ['id' => $p->id]);
    }

    public function test_nao_existe_forma_de_arquivar(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('gerir.procedimentos.archive'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('procedures', 'archived_at'));
    }

    // ---------------- Consulta, pesquisa e impressão ----------------

    public function test_consulta_mostra_procedimentos_e_regras(): void
    {
        $admin = $this->pessoa();
        SafetyRule::create(['position' => 1, 'content' => 'Desligar sempre da corrente.']);
        $this->criarProcedimento($admin, ['problem' => 'Luz vermelha no painel']);

        $this->actingAs($admin)->get('/')->assertOk()
            ->assertSee('Regras de segurança')
            ->assertSee('Desligar sempre da corrente.')
            ->assertSee('PROC-01')
            ->assertSee('Problema / sintomas')
            ->assertSee('Luz vermelha no painel')
            ->assertSee('Modelo e número de série');
    }

    public function test_pesquisa_e_filtro(): void
    {
        $admin = $this->pessoa();
        $redes = $this->categoria('Redes');
        $this->criarProcedimento($admin);
        $this->criarProcedimento($admin, [
            'title' => 'Reiniciar router', 'category_id' => $redes->id, 'steps' => ['Desligar 30 segundos'],
        ]);

        $this->actingAs($admin);
        $this->get('/?q=router')->assertSee('Reiniciar router')->assertDontSee('Substituir toner');
        $this->get('/?q=TAMPA')->assertSee('Substituir toner');
        $this->get('/?categoria='.$redes->id)->assertSee('Reiniciar router')->assertDontSee('Substituir toner');
        $this->get('/?q=inexistente')->assertSee('Sem resultados');
        $this->get('/?q=100%')->assertOk();
    }

    public function test_paginas_de_impressao(): void
    {
        $admin = $this->pessoa();
        SafetyRule::create(['position' => 1, 'content' => 'Usar pulseira antiestática.']);
        $p = $this->criarProcedimento($admin);

        $this->actingAs($admin)->get(route('imprimir'))->assertOk()
            ->assertSee('Usar pulseira antiestática.')->assertSee('PROC-01');
        $this->actingAs($admin)->get(route('imprimir.um', $p))->assertOk()
            ->assertSee('Substituir toner')->assertDontSee('Usar pulseira antiestática.');
    }

    // ---------------- Categorias e regras ----------------

    public function test_categorias_crud_e_proteccao_ao_eliminar(): void
    {
        $admin = $this->pessoa();

        $this->actingAs($admin)->post(route('gerir.categorias.store'), ['name' => 'Portáteis'])->assertRedirect();
        $this->actingAs($admin)->post(route('gerir.categorias.store'), ['name' => 'Portáteis'])
            ->assertSessionHasErrors(['name' => 'Já existe uma categoria com esse nome.']);

        $cat = Category::where('name', 'Portáteis')->first();
        $this->criarProcedimento($admin, ['category_id' => $cat->id]);

        $this->actingAs($admin)->delete(route('gerir.categorias.destroy', $cat))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $cat->id]);

        Procedure::query()->delete();
        $this->actingAs($admin)->delete(route('gerir.categorias.destroy', $cat))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);
    }

    public function test_regras_crud_e_reordenacao(): void
    {
        $admin = $this->pessoa();

        $this->actingAs($admin)->post(route('gerir.regras.store'), ['content' => 'Regra A'])->assertRedirect();
        $this->actingAs($admin)->post(route('gerir.regras.store'), ['content' => 'Regra B'])->assertRedirect();
        $this->actingAs($admin)->post(route('gerir.regras.store'), ['content' => ''])->assertSessionHasErrors('content');

        [$a, $b] = SafetyRule::orderBy('position')->get();
        $this->actingAs($admin)->post(route('gerir.regras.move', $b), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['Regra B', 'Regra A'], SafetyRule::orderBy('position')->pluck('content')->all());

        $this->actingAs($admin)->delete(route('gerir.regras.destroy', $b))->assertRedirect();
        $this->assertSame([1], SafetyRule::pluck('position')->all());
    }

    // ---------------- Perfis órfãos ----------------

    public function test_o_comando_varre_perfis_de_gente_que_ja_nao_existe(): void
    {
        // O perfil vive nesta base de dados e a conta na do portal: são bases
        // diferentes, por isso quem for eliminado lá deixa cá o perfil. É
        // inofensivo (ninguém o lê), mas é lixo.
        $pessoa = $this->pessoa();
        $orfao = Perfil::create(['utilizador_id' => 99999, 'papel' => 'editor', 'area' => 'tecnica']);

        $this->artisan('perfis:limpar')->assertSuccessful();

        $this->assertNull(Perfil::find($orfao->id), 'o perfil órfão devia ter sido apagado');
        $this->assertNotNull(
            Perfil::where('utilizador_id', $pessoa->id)->first(),
            'o perfil de quem existe tem de ficar'
        );
    }

    public function test_o_comando_com_mostrar_nao_apaga_nada(): void
    {
        $orfao = Perfil::create(['utilizador_id' => 99998, 'papel' => 'leitor', 'area' => 'producao']);

        $this->artisan('perfis:limpar --mostrar')->assertSuccessful();

        $this->assertNotNull(Perfil::find($orfao->id));
    }
}
