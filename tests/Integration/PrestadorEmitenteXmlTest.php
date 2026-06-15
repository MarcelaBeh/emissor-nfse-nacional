<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Integration;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Dps;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Endereco;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Prestador;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Tomador;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\MotivoEmissaoTI;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\RegimeTributario;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoAmbiente;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoEmitente;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cep;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Builder\DpsXmlBuilder;
use MarcelaBeh\EmissorNfseNacional\Infrastructure\Xml\Validator\XsdValidator;
use PHPUnit\Framework\TestCase;

final class PrestadorEmitenteXmlTest extends TestCase
{
    private DpsXmlBuilder $builder;
    private XsdValidator $xsdValidator;

    protected function setUp(): void
    {
        $this->builder = new DpsXmlBuilder();
        $this->xsdValidator = new XsdValidator();
    }

    public function test_prestador_emitente_omite_xnome_e_endereco(): void
    {
        $dps = $this->createDps(TipoEmitente::PRESTADOR, comEnderecoPrestador: true);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        // A SEFAZ proíbe (E0121/E0128): o grupo <prest> não pode conter xNome nem <end>.
        $prest = $this->extrairPrest($xml);
        self::assertStringNotContainsString('<xNome>', $prest, 'tpEmit=1 não deve emitir xNome (E0121)');
        self::assertStringNotContainsString('<end>', $prest, 'tpEmit=1 não deve emitir <end> (E0128)');

        // E mesmo omitindo, deve continuar válido contra o XSD (campos minOccurs=0).
        $this->xsdValidator->validate($xml, 'DPS');
    }

    public function test_prestador_nao_emitente_mantem_xnome_e_endereco(): void
    {
        $dps = $this->createDps(TipoEmitente::TOMADOR, comEnderecoPrestador: true);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $prest = $this->extrairPrest($xml);
        self::assertStringContainsString('<xNome>', $prest, 'tpEmit!=1 deve manter xNome');
        self::assertStringContainsString('<end>', $prest, 'tpEmit!=1 deve manter <end>');

        $this->xsdValidator->validate($xml, 'DPS');
    }

    public function test_prestador_emitente_sem_endereco_valida_no_xsd(): void
    {
        // Caso real autorizado: prestador-emitente sem endereço informado.
        $dps = $this->createDps(TipoEmitente::PRESTADOR, comEnderecoPrestador: false);
        $dps->gerarChaveAcesso();

        $xml = $this->builder->build($dps);

        $this->xsdValidator->validate($xml, 'DPS');

        $prest = $this->extrairPrest($xml);
        self::assertStringNotContainsString('<end>', $prest);
    }

    /**
     * Isola o trecho <prest>...</prest> para asserções localizadas (evita falso
     * positivo de <end>/<xNome> que existam no tomador).
     */
    private function extrairPrest(string $xml): string
    {
        self::assertSame(1, preg_match('#<prest>.*?</prest>#s', $xml, $m), 'Bloco <prest> não encontrado');

        return $m[0];
    }

    private function createDps(TipoEmitente $tpEmit, bool $comEnderecoPrestador): Dps
    {
        $endereco = new Endereco(
            logradouro: 'Rua Teste',
            numero: '123',
            complemento: null,
            bairro: 'Centro',
            codigoMunicipio: new CodigoMunicipio('3550308'),
            uf: 'SP',
            cep: new Cep('01001001'),
        );

        return new Dps(
            tipoAmbiente: TipoAmbiente::HOMOLOGACAO,
            dataEmissao: new \DateTimeImmutable('2026-06-15T10:00:00'),
            versaoAplicacao: '1.0.0',
            serie: 1,
            numero: 123,
            dataCompetencia: new \DateTimeImmutable('2026-06-01'),
            tipoEmissao: $tpEmit,
            // cMotivoEmisTI é obrigatório quando o emitente é Tomador/Intermediário (tpEmit≠1).
            cMotivoEmisTI: $tpEmit === TipoEmitente::PRESTADOR ? null : MotivoEmissaoTI::IMPORTACAO_SERVICO,
            codigoMunicipioEmissor: new CodigoMunicipio('3550308'),
            prestador: new Prestador(
                documento: new Cnpj('11444777000161'),
                inscricaoMunicipal: '123456',
                razaoSocial: 'Prestador Ltda',
                telefone: null,
                email: null,
                endereco: $comEnderecoPrestador ? $endereco : null,
                regimeTributario: RegimeTributario::SIMPLES_NACIONAL,
            ),
            tomador: new Tomador(
                documento: new Cnpj('33444555000181'),
                razaoSocial: 'Tomador Ltda',
                telefone: null,
                email: null,
                endereco: $endereco,
            ),
            servico: new Servico(
                discriminacao: 'Serviço de teste',
                codigoTributacao: '010101',
                localPrestacao: new CodigoMunicipio('3550308'),
                valorServicos: new Money(1000.00),
                descontoIncondicionado: new Money(0),
                descontoCondicionado: new Money(0),
                aliquotaIss: 5.0,
                codigoNbs: '123456789',
            ),
        );
    }
}
