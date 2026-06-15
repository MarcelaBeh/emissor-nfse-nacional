<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\AtvEvento;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\BeneficioMunicipal;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ComExterior;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\ExigSusp;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\InfoCompl;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Obra;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\Servico;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\TribFederal;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TipoRetencaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\Enum\TributacaoIssqn;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\CodigoMunicipio;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class ServicoTest extends TestCase
{
    public function test_create_minimal(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
        );

        $this->assertSame('test', $servico->getDiscriminacao());
        $this->assertSame('010101', $servico->getCodigoTributacao());
        $this->assertSame('3550308', $servico->getLocalPrestacao()->getCodigo());
        $this->assertSame(1000.00, $servico->getValorTotal()->getValue());
        $this->assertNull($servico->getCodigoNbs());
        $this->assertNull($servico->getObra());
        $this->assertSame('1', $servico->getTribISSQN()->value);
        $this->assertSame('1', $servico->getTpRetISSQN()->value);
        $this->assertNull($servico->getComExterior());
        $this->assertNull($servico->getAtvEvento());
        $this->assertNull($servico->getInfoCompl());
        $this->assertNull($servico->getDocumentosDeducao());
    }

    public function test_calculates_valor_total(): void
    {
        // Dedução informada via <vDR> (valorDeducaoPadrao) deve reduzir a base de cálculo.
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(2000.00),
            descontoIncondicionado: new Money(50.00),
            descontoCondicionado: new Money(30.00),
            aliquotaIss: 5.0,
            valorDeducaoPadrao: 200.00,
        );

        $this->assertSame(1920.00, $servico->getValorTotal()->getValue());
        $this->assertSame(1800.00, $servico->getBaseCalculo()->getValue());
        $this->assertSame(90.00, $servico->getValorIss()->getValue());
    }

    public function test_deducao_percentual_reduz_base_calculo(): void
    {
        // <pDR>: 10% de 2000 = 200 de dedução → BC = 1800.
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(2000.00),
            aliquotaIss: 5.0,
            percentualDeducao: 10.0,
        );

        $this->assertSame(1800.00, $servico->getBaseCalculo()->getValue());
        $this->assertSame(90.00, $servico->getValorIss()->getValue());
    }

    public function test_deducao_por_documentos_soma_reduz_base_calculo(): void
    {
        // Σ vDeducaoReducao = 150 + 50 = 200 → BC = 1800.
        $docs = [
            new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed(
                tipoDocumento: 'nDoc',
                dataEmissaoDoc: new \DateTimeImmutable('2026-05-15'),
                numeroDoc: 'A',
                tipoDeducaoReducao: '1',
                valorDedutivel: '150.00',
                valorDeducao: '150.00',
            ),
            new \MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed(
                tipoDocumento: 'nDoc',
                dataEmissaoDoc: new \DateTimeImmutable('2026-05-15'),
                numeroDoc: 'B',
                tipoDeducaoReducao: '1',
                valorDedutivel: '50.00',
                valorDeducao: '50.00',
            ),
        ];

        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(2000.00),
            aliquotaIss: 5.0,
            documentosDeducao: $docs,
        );

        $this->assertSame(1800.00, $servico->getBaseCalculo()->getValue());
        $this->assertSame(90.00, $servico->getValorIss()->getValue());
    }

    public function test_beneficio_municipal_valor_reduz_base_calculo(): void
    {
        // vRedBCBM = 400 → BC = 2000 - 400 = 1600.
        $bm = new BeneficioMunicipal(numeroBeneficio: '35503080100001', valorReducaoBC: 400.00);
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(2000.00),
            aliquotaIss: 5.0,
            beneficioMunicipal: $bm,
        );

        $this->assertSame(1600.00, $servico->getBaseCalculo()->getValue());
        $this->assertSame(80.00, $servico->getValorIss()->getValue());
    }

    public function test_beneficio_municipal_percentual_reduz_base_calculo(): void
    {
        // pRedBCBM = 20% de 2000 = 400 → BC = 1600.
        $bm = new BeneficioMunicipal(numeroBeneficio: '35503080100001', percentualReducaoBC: 20.0);
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(2000.00),
            aliquotaIss: 5.0,
            beneficioMunicipal: $bm,
        );

        $this->assertSame(1600.00, $servico->getBaseCalculo()->getValue());
        $this->assertSame(80.00, $servico->getValorIss()->getValue());
    }

    public function test_create_with_all_optionals(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            codigoNbs: '12345678',
            tribISSQN: TributacaoIssqn::EXPORTACAO,
            tpRetISSQN: TipoRetencaoIssqn::RETIDO_TOMADOR,
            codigoPaisPrestacao: '01058',
            codigoTributacaoMunicipal: '3550308',
            codigoInternoContribuinte: 'contrib123',
            valorRecebido: 950.0,
            tipoImunidade: 1,
            totTribTipo: 'SN',
            pTotTribFed: 4.0,
            pTotTribEst: 2.0,
            pTotTribMun: 3.0,
            indTotTrib: 'S',
            pTotTribSN: 9.0,
        );

        $this->assertSame('12345678', $servico->getCodigoNbs());
        $this->assertSame('3', $servico->getTribISSQN()->value);
        $this->assertSame('2', $servico->getTpRetISSQN()->value);
        $this->assertSame('01058', $servico->getCodigoPaisPrestacao());
        $this->assertSame('3550308', $servico->getCodigoTributacaoMunicipal());
        $this->assertSame('contrib123', $servico->getCodigoInternoContribuinte());
        $this->assertSame(950.0, $servico->getValorRecebido());
        $this->assertSame(1, $servico->getTipoImunidade());
        $this->assertSame('SN', $servico->getTotTribTipo());
        $this->assertSame(4.0, $servico->getPTotTribFed());
        $this->assertSame(2.0, $servico->getPTotTribEst());
        $this->assertSame(3.0, $servico->getPTotTribMun());
        $this->assertSame('S', $servico->getIndTotTrib());
        $this->assertSame(9.0, $servico->getPTotTribSN());
    }

    public function test_create_with_obra(): void
    {
        $obra = new Obra(inscImobFisc: '12345', cObra: 'CNO123456789');
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            obra: $obra,
        );

        $this->assertSame($obra, $servico->getObra());
        $this->assertSame('CNO123456789', $servico->getObra()->getCObra());
    }

    public function test_create_with_com_exterior(): void
    {
        $comExterior = new ComExterior(
            modoPrestacao: 1,
            vinculoPrestador: 1,
            codigoMoeda: '840',
            valorServicoMoeda: 1000.0,
            mecanismoApoioPrestador: '01',
            mecanismoApoioTomador: '01',
            movimentacaoTemporaria: '0',
            enviarMDIC: '0',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            comExterior: $comExterior,
        );

        $this->assertSame($comExterior, $servico->getComExterior());
        $this->assertSame('840', $servico->getComExterior()->getCodigoMoeda());
    }

    public function test_create_with_atv_evento(): void
    {
        $atvEvento = new AtvEvento(
            descricao: 'Evento teste',
            dataInicio: new \DateTimeImmutable('2026-05-15'),
            dataFim: new \DateTimeImmutable('2026-05-16'),
            identificacaoEvento: 'EVT-001',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            atvEvento: $atvEvento,
        );

        $this->assertSame($atvEvento, $servico->getAtvEvento());
        $this->assertSame('EVT-001', $servico->getAtvEvento()->getIdentificacaoEvento());
    }

    public function test_create_with_info_compl(): void
    {
        $infoCompl = new InfoCompl(
            infoComplementar: 'teste complementar',
            numeroPedido: 'PED-001',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            infoCompl: $infoCompl,
        );

        $this->assertSame($infoCompl, $servico->getInfoCompl());
        $this->assertSame('teste complementar', $servico->getInfoCompl()->getInfoComplementar());
    }

    public function test_create_with_documentos_deducao(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'chNFe',
            chaveNFe: '12345678901234567890123456789012345678901234',
            tipoDeducaoReducao: '1',
            dataEmissaoDoc: new \DateTimeImmutable('2026-05-15'),
            valorDedutivel: '1000.00',
            valorDeducao: '1000.00',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            documentosDeducao: [$doc],
        );

        $this->assertCount(1, $servico->getDocumentosDeducao());
        $this->assertSame($doc, $servico->getDocumentosDeducao()[0]);
    }

    public function test_create_with_exig_susp_muni_beneficio_trib(): void
    {
        $exigSusp = new ExigSusp(tipoSuspensao: 1, numeroProcesso: '000000000000000000000000012345');
        $bm = new BeneficioMunicipal(numeroBeneficio: '00000000000001');
        $tribFederal = new TribFederal(
            pisCofinsTipo: '1',
            pisCofinsCst: '01',
            valorRetidoIRRF: '100.00',
            valorRetidoCSLL: '200.00',
        );
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
            exigSusp: $exigSusp,
            beneficioMunicipal: $bm,
            tribFederal: $tribFederal,
        );

        $this->assertSame($exigSusp, $servico->getExigSusp());
        $this->assertSame(1, $servico->getExigSusp()->getTipoSuspensao());
        $this->assertSame($bm, $servico->getBeneficioMunicipal());
        $this->assertSame($tribFederal, $servico->getTribFederal());
        $this->assertSame('01', $servico->getTribFederal()->getPisCofinsCst());
    }

    public function test_empty_discriminacao_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Discriminação do serviço é obrigatória');

        new Servico(
            discriminacao: '',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
        );
    }

    public function test_discriminacao_exceeds_2000_chars_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('2000 caracteres');

        new Servico(
            discriminacao: str_repeat('A', 2001),
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
        );
    }

    public function test_aliquota_iss_negative_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alíquota ISS');

        new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: -1.0,
        );
    }

    public function test_aliquota_iss_above_100_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alíquota ISS');

        new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(1000.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 101.0,
        );
    }

    public function test_negative_valor_total_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor total deve ser positivo');

        new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(100.00),
            descontoIncondicionado: new Money(200.00),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
        );
    }

    public function test_get_valor_total_without_discounts(): void
    {
        $servico = new Servico(
            discriminacao: 'test',
            codigoTributacao: '010101',
            localPrestacao: new CodigoMunicipio('3550308'),
            valorServicos: new Money(500.00),
            descontoIncondicionado: new Money(0),
            descontoCondicionado: new Money(0),
            aliquotaIss: 5.0,
        );

        $this->assertSame(500.00, $servico->getValorTotal()->getValue());
    }
}
