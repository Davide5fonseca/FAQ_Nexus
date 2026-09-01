<?php

namespace Tests\Feature;

use App\Models\Anexo;
use App\Models\Category;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Anexos dos procedimentos.
 *
 * O que mais importa aqui não é o carregamento em si: é que a separação por
 * áreas continue a valer para os ficheiros. Um anexo vive fora da pasta
 * pública justamente para que ninguém o veja só por saber o endereço.
 */
class AnexoTest extends TestCase
{
    use RefreshDatabase;

    private int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Anexo::DISCO);

        DB::connection(config('database.suite'))->table('aplicacoes')->insert([
            'id' => 1, 'chave' => config('app.chave'), 'nome' => 'Knowledgebase',
            'url' => 'https://exemplo.pt/kb', 'activa' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function pessoa(string $papel = 'admin', string $area = 'tecnica'): User
    {
        $this->contador++;

        $user = User::forceCreate([
            'nome' => "Pessoa {$this->contador}",
            'email' => "pessoa{$this->contador}@teste.pt",
            'password' => Hash::make('palavrapasse123'),
            'papel' => 'tecnico',
            'ativo' => true,
        ]);

        // O papel e a área vêm na própria linha de acesso, decidida no portal.
        DB::connection(config('database.suite'))->table('acessos')->insert([
            'utilizador_id' => $user->id, 'aplicacao_id' => 1,
            'papel' => $papel, 'contexto' => $area,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user->fresh();
    }

    private function criar(User $user, array $dados = []): Procedure
    {
        $cat = Category::firstOrCreate(['name' => 'Impressoras']);

        $this->actingAs($user)->post(route('gerir.procedimentos.store'), array_merge([
            'title' => 'Substituir toner',
            'category_id' => $cat->id,
            'steps' => ['Desligar', 'Trocar'],
        ], $dados))->assertRedirect(route('gerir.procedimentos.index'));

        return Procedure::latest('id')->first();
    }

    private function imagem(string $nome = 'ecra.png'): UploadedFile
    {
        return UploadedFile::fake()->image($nome, 800, 600);
    }

    // ---------------- Juntar ----------------

    public function test_juntar_uma_imagem_ao_criar_o_procedimento(): void
    {
        $admin = $this->pessoa();

        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);

        $this->assertCount(1, $procedimento->anexos);

        $anexo = $procedimento->anexos->first();
        $this->assertSame('ecra.png', $anexo->nome_original);
        $this->assertStringStartsWith('image/', $anexo->tipo);
        $this->assertTrue($anexo->existeNoDisco());

        // O nome no disco é gerado por nós, nunca o que veio de fora.
        $this->assertNotSame('ecra.png', $anexo->ficheiro);
    }

    public function test_juntar_uma_imagem_a_um_procedimento_que_ja_existe(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin);
        $cat = Category::first();

        $this->actingAs($admin)->put(route('gerir.procedimentos.update', $procedimento), [
            'title' => $procedimento->title,
            'category_id' => $cat->id,
            'steps' => ['Desligar'],
            'anexos' => [$this->imagem('placa.jpg')],
            'legendas' => ['Vista da placa'],
        ])->assertRedirect(route('gerir.procedimentos.index'));

        $anexo = $procedimento->fresh()->anexos->first();
        $this->assertSame('Vista da placa', $anexo->legenda);
        $this->assertSame('Vista da placa', $anexo->rotulo);
    }

    public function test_um_ficheiro_de_tipo_nao_aceite_e_recusado(): void
    {
        $admin = $this->pessoa();
        $cat = Category::firstOrCreate(['name' => 'Impressoras']);

        $this->actingAs($admin)->post(route('gerir.procedimentos.store'), [
            'title' => 'Com ficheiro estranho',
            'category_id' => $cat->id,
            'steps' => ['Um passo'],
            'anexos' => [UploadedFile::fake()->create('script.exe', 10)],
        ])->assertSessionHasErrors('anexos.0');

        $this->assertSame(0, Anexo::count());
    }

    public function test_um_ficheiro_grande_demais_e_recusado(): void
    {
        $admin = $this->pessoa();
        $cat = Category::firstOrCreate(['name' => 'Impressoras']);

        $this->actingAs($admin)->post(route('gerir.procedimentos.store'), [
            'title' => 'Com ficheiro enorme',
            'category_id' => $cat->id,
            'steps' => ['Um passo'],
            'anexos' => [UploadedFile::fake()->create('enorme.pdf', Anexo::TAMANHO_MAXIMO_KB + 1, 'application/pdf')],
        ])->assertSessionHasErrors('anexos.0');
    }

    public function test_nao_se_passa_do_limite_de_anexos(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin);
        $cat = Category::first();

        // Duas vezes o limite, de uma assentada: só entram os que cabem.
        $muitos = [];
        for ($i = 0; $i < Anexo::MAXIMO_POR_PROCEDIMENTO; $i++) {
            $muitos[] = $this->imagem("ecra{$i}.png");
        }

        $this->actingAs($admin)->put(route('gerir.procedimentos.update', $procedimento), [
            'title' => $procedimento->title,
            'category_id' => $cat->id,
            'steps' => ['Desligar'],
            'anexos' => $muitos,
        ]);

        $this->actingAs($admin)->put(route('gerir.procedimentos.update', $procedimento), [
            'title' => $procedimento->title,
            'category_id' => $cat->id,
            'steps' => ['Desligar'],
            'anexos' => [$this->imagem('a_mais.png')],
        ]);

        $this->assertSame(
            Anexo::MAXIMO_POR_PROCEDIMENTO,
            $procedimento->fresh()->anexos->count()
        );
    }

    // ---------------- Ver: é aqui que a área tem de valer ----------------

    public function test_quem_ve_o_procedimento_ve_o_anexo(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        $this->actingAs($admin)
            ->get(route('anexo', [$procedimento, $anexo]))
            ->assertOk()
            ->assertHeader('Content-Type', $anexo->tipo);
    }

    public function test_quem_e_de_outra_area_nao_ve_o_anexo(): void
    {
        $tecnico = $this->pessoa('editor', 'tecnica');
        $procedimento = $this->criar($tecnico, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        $producao = $this->pessoa('editor', 'producao');

        $this->actingAs($producao)
            ->get(route('anexo', [$procedimento, $anexo]))
            ->assertForbidden();
    }

    public function test_sem_sessao_nao_se_ve_o_anexo(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        // Sair mesmo: o actingAs usado para criar o procedimento mantinha-se
        // até ao fim do teste, e sem isto não se estaria a testar nada.
        auth()->logout();
        $this->app['auth']->forgetGuards();

        $this->get(route('anexo', [$procedimento, $anexo]))
            ->assertRedirect(config('app.portal_url'));
    }

    public function test_nao_se_chega_a_um_anexo_pelo_procedimento_errado(): void
    {
        $admin = $this->pessoa();
        $comAnexo = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $outro = $this->criar($admin, ['title' => 'Outro procedimento']);
        $anexo = $comAnexo->anexos->first();

        // Trocar o número do procedimento no endereço não dá acesso ao anexo.
        $this->actingAs($admin)
            ->get(route('anexo', [$outro, $anexo]))
            ->assertNotFound();
    }

    // ---------------- Retirar ----------------

    public function test_retirar_um_anexo_apaga_tambem_o_ficheiro(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();
        $caminho = $anexo->caminho();

        Storage::disk(Anexo::DISCO)->assertExists($caminho);

        $this->actingAs($admin)
            ->delete(route('gerir.procedimentos.anexos.destroy', [$procedimento, $anexo]))
            ->assertRedirect();

        $this->assertSame(0, Anexo::count());
        Storage::disk(Anexo::DISCO)->assertMissing($caminho);
    }

    public function test_apagar_o_procedimento_leva_os_ficheiros_dos_anexos(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem(), $this->imagem('outra.png')]]);
        $caminhos = $procedimento->anexos->map->caminho();

        $this->actingAs($admin)
            ->delete(route('gerir.procedimentos.destroy', $procedimento))
            ->assertRedirect(route('gerir.procedimentos.index'));

        $this->assertSame(0, Anexo::count());
        foreach ($caminhos as $caminho) {
            Storage::disk(Anexo::DISCO)->assertMissing($caminho);
        }
    }

    public function test_quem_e_de_outra_area_nao_retira_anexos(): void
    {
        $tecnico = $this->pessoa('editor', 'tecnica');
        $procedimento = $this->criar($tecnico, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        $producao = $this->pessoa('admin', 'producao');

        // Administrador de produção: vê todas as áreas, por isso pode.
        $this->actingAs($producao)
            ->delete(route('gerir.procedimentos.anexos.destroy', [$procedimento, $anexo]))
            ->assertRedirect();

        $this->assertSame(0, Anexo::count());
    }

    public function test_um_leitor_nao_junta_nem_retira_anexos(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        $leitor = $this->pessoa('leitor', 'tecnica');

        $this->actingAs($leitor)
            ->delete(route('gerir.procedimentos.anexos.destroy', [$procedimento, $anexo]))
            ->assertForbidden();

        $this->assertSame(1, Anexo::count());
    }

    // ---------------- Onde aparecem ----------------

    public function test_o_anexo_aparece_na_consulta(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        $this->actingAs($admin)->get(route('consulta'))
            ->assertOk()
            ->assertSee(route('anexo', [$procedimento, $anexo]), false);
    }

    public function test_as_imagens_saem_na_impressao(): void
    {
        $admin = $this->pessoa();
        $procedimento = $this->criar($admin, ['anexos' => [$this->imagem()]]);
        $anexo = $procedimento->anexos->first();

        $this->actingAs($admin)->get(route('imprimir'))
            ->assertOk()
            ->assertSee('Imagens')
            ->assertSee(route('anexo', [$procedimento, $anexo]), false);
    }
}
