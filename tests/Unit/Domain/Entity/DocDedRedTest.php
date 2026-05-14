<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional\Tests\Unit\Domain\Entity;

use MarcelaBeh\EmissorNfseNacional\Domain\Entity\DocDedRed;
use MarcelaBeh\EmissorNfseNacional\Domain\Entity\IbsCbsFornecedor;
use MarcelaBeh\EmissorNfseNacional\Domain\ValueObject\Cnpj;
use PHPUnit\Framework\TestCase;

final class DocDedRedTest extends TestCase
{
    public function test_create_ch_nfse(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'chNFSe',
            chaveNFSe: '12345678901234567890123456789012345678901234567890',
            tipoDeducaoReducao: '1',
            dataEmissaoDoc: new \DateTimeImmutable('2026-05-15'),
            valorDedutivel: '1500.00',
            valorDeducao: '1500.00',
        );

        $this->assertSame('chNFSe', $doc->getTipoDocumento());
        $this->assertSame('12345678901234567890123456789012345678901234567890', $doc->getChaveNFSe());
        $this->assertNull($doc->getChaveNFe());
        $this->assertSame('1', $doc->getTipoDeducaoReducao());
        $this->assertEquals(new \DateTimeImmutable('2026-05-15'), $doc->getDataEmissaoDoc());
        $this->assertSame('1500.00', $doc->getValorDedutivel());
        $this->assertSame('1500.00', $doc->getValorDeducao());
    }

    public function test_create_ch_nfe(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'chNFe',
            chaveNFe: '12345678901234567890123456789012345678901234',
            tipoDeducaoReducao: '2',
            dataEmissaoDoc: new \DateTimeImmutable('2026-05-20'),
            valorDedutivel: '3000.00',
            valorDeducao: '3000.00',
        );

        $this->assertSame('chNFe', $doc->getTipoDocumento());
        $this->assertSame('12345678901234567890123456789012345678901234', $doc->getChaveNFe());
        $this->assertNull($doc->getChaveNFSe());
    }

    public function test_create_nf_se_mun(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'NFSeMun',
            codigoMunicipioNFSe: '3550308',
            numeroNFSe: '12345',
            codigoVerificacaoNFSe: 'ABC123',
            tipoDeducaoReducao: '1',
            dataEmissaoDoc: new \DateTimeImmutable('2026-06-01'),
            valorDedutivel: '500.00',
            valorDeducao: '500.00',
        );

        $this->assertSame('NFSeMun', $doc->getTipoDocumento());
        $this->assertSame('3550308', $doc->getCodigoMunicipioNFSe());
        $this->assertSame('12345', $doc->getNumeroNFSe());
        $this->assertSame('ABC123', $doc->getCodigoVerificacaoNFSe());
    }

    public function test_create_nf_nfs(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'NFNFS',
            numeroNFS: 'NFS-001',
            modeloNFS: 'M1',
            serieNFS: 'S1',
            tipoDeducaoReducao: '3',
            dataEmissaoDoc: new \DateTimeImmutable('2026-06-15'),
            valorDedutivel: '2000.00',
            valorDeducao: '2000.00',
        );

        $this->assertSame('NFNFS', $doc->getTipoDocumento());
        $this->assertSame('NFS-001', $doc->getNumeroNFS());
        $this->assertSame('M1', $doc->getModeloNFS());
        $this->assertSame('S1', $doc->getSerieNFS());
    }

    public function test_create_n_doc_fisc(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'nDocFisc',
            numeroDocFiscal: 'NF-2026-001',
            tipoDeducaoReducao: '99',
            descricaoOutrasDeducoes: 'Documento fiscal diversos',
            dataEmissaoDoc: new \DateTimeImmutable('2026-07-01'),
            valorDedutivel: '1000.00',
            valorDeducao: '1000.00',
        );

        $this->assertSame('nDocFisc', $doc->getTipoDocumento());
        $this->assertSame('NF-2026-001', $doc->getNumeroDocFiscal());
        $this->assertNull($doc->getNumeroDoc());
    }

    public function test_create_n_doc(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'nDoc',
            numeroDoc: 'REC-2026-001',
            tipoDeducaoReducao: '99',
            descricaoOutrasDeducoes: 'Recibo de despesas',
            dataEmissaoDoc: new \DateTimeImmutable('2026-07-15'),
            valorDedutivel: '750.00',
            valorDeducao: '750.00',
        );

        $this->assertSame('nDoc', $doc->getTipoDocumento());
        $this->assertSame('REC-2026-001', $doc->getNumeroDoc());
        $this->assertNull($doc->getNumeroDocFiscal());
        $this->assertSame('99', $doc->getTipoDeducaoReducao());
        $this->assertSame('Recibo de despesas', $doc->getDescricaoOutrasDeducoes());
    }

    public function test_create_with_fornecedor(): void
    {
        $fornecedor = new IbsCbsFornecedor(
            xNome: 'Fornecedor Ltda',
            cnpj: new Cnpj('11444777000161'),
        );

        $doc = new DocDedRed(
            tipoDocumento: 'nDoc',
            numeroDoc: 'REC-002',
            tipoDeducaoReducao: '1',
            dataEmissaoDoc: new \DateTimeImmutable('2026-08-01'),
            valorDedutivel: '200.00',
            valorDeducao: '200.00',
            fornecedor: $fornecedor,
        );

        $this->assertSame($fornecedor, $doc->getFornecedor());
        $this->assertSame('Fornecedor Ltda', $doc->getFornecedor()->getXNome());
    }

    public function test_create_without_fornecedor(): void
    {
        $doc = new DocDedRed(
            tipoDocumento: 'chNFe',
            chaveNFe: '12345678901234567890123456789012345678901234',
            tipoDeducaoReducao: '1',
            dataEmissaoDoc: new \DateTimeImmutable('2026-08-01'),
            valorDedutivel: '100.00',
            valorDeducao: '100.00',
        );

        $this->assertNull($doc->getFornecedor());
    }
}
