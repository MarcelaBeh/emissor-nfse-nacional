<?php

declare(strict_types=1);

namespace MarcelaBeh\EmissorNfseNacional;

use DOMNode;
use MarcelaBeh\EmissorNfseNacional\Validators\CnpjValidator;
use MarcelaBeh\EmissorNfseNacional\Validators\CodigoIbgeValidator;
use MarcelaBeh\EmissorNfseNacional\Validators\CpfValidator;
use MarcelaBeh\EmissorNfseNacional\Validators\MotivoSubstituicaoValidator;
use NFePHP\Common\DOMImproved as Dom;
use stdClass;

class Dps implements DpsInterface
{
    public stdClass $std;
    protected DOMNode $dpsNode;
    protected DOMNode $eventoNode;
    protected Dom $dom;
    private string $dpsId;
    private string $preId;

    public function __construct(?stdClass $std = null)
    {
        $this->init($std);
        $this->dom = new Dom('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
    }

    private function init(?stdClass $dps = null): void
    {
        if (!empty($dps)) {
            $this->std = self::propertiesToLower($dps);
            if (empty($this->std->version)) {
                $this->std->version = '1.01';
            }
        }
    }

    public function render(?stdClass $std = null): string
    {
        if ($this->dom->hasChildNodes()) {
            $this->dom = new Dom('1.0', 'UTF-8');
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = false;
        }

        $this->init($std);

        $this->dpsNode = $this->dom->createElement('DPS');
        $this->dpsNode->setAttribute('versao', $this->std->version);
        $this->dpsNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infDps = $this->dom->createElement('infDPS');
        $infDps->setAttribute('Id', $this->generateId());

        $this->addChild($infDps, 'tpAmb', $this->std->infdps->tpamb, true);
        $this->addChild($infDps, 'dhEmi', $this->std->infdps->dhemi, true);
        $this->addChild($infDps, 'verAplic', $this->std->infdps->veraplic, true);
        $this->addChild($infDps, 'serie', $this->std->infdps->serie, true);
        $this->addChild($infDps, 'nDPS', $this->std->infdps->ndps, true);
        $this->addChild($infDps, 'dCompet', $this->std->infdps->dcompet, true);
        $this->addChild($infDps, 'tpEmit', $this->std->infdps->tpemit, true);
        $this->addChildOptional($infDps, 'cMotivoEmisTI', $this->std->infdps->cmotivoemisti ?? null);
        $this->addChildOptional($infDps, 'chNFSeRej', $this->std->infdps->chnfserej ?? null);
        $this->addChild($infDps, 'cLocEmi', $this->std->infdps->clocemi, true);

        if (isset($this->std->infdps->subst)) {
            $this->buildSubst($infDps);
        }

        if (isset($this->std->infdps->prest)) {
            $this->buildPrestador($infDps);
        }

        if (isset($this->std->infdps->toma)) {
            $this->buildPessoa($infDps, 'toma', $this->std->infdps->toma);
        }

        if (isset($this->std->infdps->interm)) {
            $this->buildPessoa($infDps, 'interm', $this->std->infdps->interm);
        }

        $this->buildServico($infDps);
        $this->buildValores($infDps);

        if (isset($this->std->infdps->ibscbs)) {
            $this->buildIbscbs($infDps);
        }

        $this->dpsNode->appendChild($infDps);
        $this->dom->appendChild($this->dpsNode);

        return $this->dom->saveXML();
    }

    public function renderEvento(?stdClass $std = null): string
    {
        if ($this->dom->hasChildNodes()) {
            $this->dom = new Dom('1.0', 'UTF-8');
            $this->dom->preserveWhiteSpace = false;
            $this->dom->formatOutput = false;
        }

        $this->init($std);

        $this->eventoNode = $this->dom->createElement('pedRegEvento');
        $this->eventoNode->setAttribute('versao', $this->std->version);
        $this->eventoNode->setAttribute('xmlns', 'http://www.sped.fazenda.gov.br/nfse');

        $infPedReg = $this->dom->createElement('infPedReg');
        $infPedReg->setAttribute('Id', $this->generatePre());

        $this->addChild($infPedReg, 'tpAmb', $this->std->infpedreg->tpamb, true);
        $this->addChild($infPedReg, 'verAplic', $this->std->infpedreg->veraplic, true);
        $this->addChild($infPedReg, 'dhEvento', $this->std->infpedreg->dhevento, true);
        $this->addChildOptional($infPedReg, 'CNPJAutor', $this->std->infpedreg->cnpjautor ?? null);
        $this->addChildOptional($infPedReg, 'CPFAutor', $this->std->infpedreg->cpfautor ?? null);
        $this->addChild($infPedReg, 'chNFSe', $this->std->infpedreg->chnfse, true);

        if (isset($this->std->infpedreg->e101101)) {
            $evt = $this->dom->createElement('e101101');
            $infPedReg->appendChild($evt);
            $this->addChild($evt, 'xDesc', $this->std->infpedreg->e101101->xdesc, true);
            $this->addChild($evt, 'cMotivo', $this->std->infpedreg->e101101->cmotivo, true);
            $this->addChild($evt, 'xMotivo', $this->std->infpedreg->e101101->xmotivo, true);
        }

        $this->eventoNode->appendChild($infPedReg);
        $this->dom->appendChild($this->eventoNode);

        return $this->dom->saveXML();
    }

    private function buildSubst(\DOMElement $parent): void
    {
        $subst = $this->dom->createElement('subst');
        $parent->appendChild($subst);
        $this->addChild($subst, 'chSubstda', $this->std->infdps->subst->chsubstda, true);

        // Validar e formatar código de motivo (obrigatório zero à esquerda)
        $codigoMotivo = MotivoSubstituicaoValidator::validateAndFormat(
            $this->std->infdps->subst->cmotivo
        );
        $this->addChild($subst, 'cMotivo', $codigoMotivo, true);

        $this->addChild($subst, 'xMotivo', $this->std->infdps->subst->xmotivo, true);
    }

    private function buildPrestador(\DOMElement $parent): void
    {
        $prest = $this->std->infdps->prest;
        $prestNode = $this->dom->createElement('prest');
        $parent->appendChild($prestNode);

        if (isset($prest->cnpj) && $prest->cnpj !== '') {
            CnpjValidator::validate($prest->cnpj);
            $this->addChild($prestNode, 'CNPJ', CnpjValidator::clean($prest->cnpj), false);
        }

        if (isset($prest->cpf) && $prest->cpf !== '') {
            CpfValidator::validate($prest->cpf);
            $this->addChild($prestNode, 'CPF', CpfValidator::clean($prest->cpf), false);
        }

        $this->addChildOptional($prestNode, 'NIF', $prest->nif ?? null);
        $this->addChildOptional($prestNode, 'cNaoNIF', $prest->cnaonif ?? null);
        $this->addChildOptional($prestNode, 'CAEPF', $prest->caepf ?? null);
        $this->addChildOptional($prestNode, 'IM', $prest->im ?? null);
        $this->addChildOptional($prestNode, 'xNome', $prest->xnome ?? null);

        if (isset($prest->end)) {
            $this->buildEndereco($prestNode, $prest->end);
        }

        $this->addChildOptional($prestNode, 'fone', $prest->fone ?? null);
        $this->addChildOptional($prestNode, 'email', $prest->email ?? null);

        $regTrib = $this->dom->createElement('regTrib');
        $prestNode->appendChild($regTrib);
        $this->addChild($regTrib, 'opSimpNac', $prest->regtrib->opsimpnac, true);
        $this->addChildOptional($regTrib, 'regApTribSN', $prest->regtrib->regaptribsn ?? null);
        $this->addChild($regTrib, 'regEspTrib', $prest->regtrib->regesptrib, true);
    }

    private function buildPessoa(\DOMElement $parent, string $tagName, stdClass $data): void
    {
        $node = $this->dom->createElement($tagName);
        $parent->appendChild($node);

        if (isset($data->cnpj) && $data->cnpj !== '') {
            CnpjValidator::validate($data->cnpj);
            $this->addChild($node, 'CNPJ', CnpjValidator::clean($data->cnpj), false);
        }

        if (isset($data->cpf) && $data->cpf !== '') {
            CpfValidator::validate($data->cpf);
            $this->addChild($node, 'CPF', CpfValidator::clean($data->cpf), false);
        }

        $this->addChildOptional($node, 'NIF', $data->nif ?? null);
        $this->addChildOptional($node, 'cNaoNIF', $data->cnaonif ?? null);
        $this->addChildOptional($node, 'CAEPF', $data->caepf ?? null);
        $this->addChildOptional($node, 'IM', $data->im ?? null);
        $this->addChild($node, 'xNome', $data->xnome, true);

        if (isset($data->end)) {
            $this->buildEndereco($node, $data->end);
        }

        $this->addChildOptional($node, 'fone', $data->fone ?? null);
        $this->addChildOptional($node, 'email', $data->email ?? null);
    }

    private function buildEndereco(\DOMElement $parent, stdClass $end): void
    {
        $endNode = $this->dom->createElement('end');
        $parent->appendChild($endNode);

        if (isset($end->endnac)) {
            $nac = $this->dom->createElement('endNac');
            $endNode->appendChild($nac);

            // Validar código IBGE (7 dígitos)
            CodigoIbgeValidator::validate($end->endnac->cmun);

            $this->addChild($nac, 'cMun', $end->endnac->cmun, true);
            $this->addChild($nac, 'CEP', $end->endnac->cep, true);
        } elseif (isset($end->endext)) {
            $ext = $this->dom->createElement('endExt');
            $endNode->appendChild($ext);
            $this->addChild($ext, 'cPais', $end->endext->cpais, true);
            $this->addChild($ext, 'cEndPost', $end->endext->cendpost, true);
            $this->addChild($ext, 'xCidade', $end->endext->xcidade, true);
            $this->addChild($ext, 'xEstProvReg', $end->endext->xestprovreg, true);
        }

        $this->addChild($endNode, 'xLgr', $end->xlgr, true);
        $this->addChild($endNode, 'nro', $end->nro, true);
        $this->addChildOptional($endNode, 'xCpl', $end->xcpl ?? null);
        $this->addChild($endNode, 'xBairro', $end->xbairro, true);
    }

    private function buildServico(\DOMElement $parent): void
    {
        $serv = $this->std->infdps->serv;
        $servNode = $this->dom->createElement('serv');
        $parent->appendChild($servNode);

        $locPrest = $this->dom->createElement('locPrest');
        $servNode->appendChild($locPrest);
        $this->addChild($locPrest, 'cLocPrestacao', $serv->locprest->clocprestacao, true);
        $this->addChildOptional($locPrest, 'cPaisPrestacao', $serv->locprest->cpaisprestacao ?? null);
        $this->addChildOptional($locPrest, 'cPaisConsum', $serv->locprest->cpaisconsum ?? null);

        $cServ = $this->dom->createElement('cServ');
        $servNode->appendChild($cServ);
        $this->addChild($cServ, 'cTribNac', $serv->cserv->ctribnac, true);
        $this->addChildOptional($cServ, 'cTribMun', $serv->cserv->ctribmun ?? null);
        $this->addChild($cServ, 'xDescServ', $serv->cserv->xdescserv, true);
        $this->addChildOptional($cServ, 'cNBS', $serv->cserv->cnbs ?? null);
        $this->addChildOptional($cServ, 'cIntContrib', $serv->cserv->cintcontrib ?? null);

        if (isset($serv->comext)) {
            $this->buildComExt($servNode, $serv->comext);
        }

        if (isset($serv->obra)) {
            $this->buildObra($servNode, $serv->obra);
        }

        if (isset($serv->atvevento)) {
            $this->buildAtvEvento($servNode, $serv->atvevento);
        }

        if (isset($serv->infocompl)) {
            $this->buildInfoCompl($servNode, $serv->infocompl);
        }
    }

    private function buildComExt(\DOMElement $parent, stdClass $comext): void
    {
        $node = $this->dom->createElement('comExt');
        $parent->appendChild($node);

        $this->addChild($node, 'mdPrestacao', $comext->mdprestacao, false);
        $this->addChild($node, 'vincPrest', $comext->vincprest, false);
        $this->addChild($node, 'tpMoeda', $comext->tpmoeda, false);
        $this->addChild($node, 'vServMoeda', $comext->vservmoeda, false);
        $this->addChild($node, 'mecAFComexP', $comext->mecafcomexp, false);
        $this->addChild($node, 'mecAFComexT', $comext->mecafcomext, false);
        $this->addChild($node, 'movTempBens', $comext->movtempbens, false);
        $this->addChildOptional($node, 'nDI', $comext->ndi ?? null);
        $this->addChildOptional($node, 'nRE', $comext->nre ?? null);
        $this->addChild($node, 'mdic', $comext->mdic, false);
    }

    private function buildObra(\DOMElement $parent, stdClass $obra): void
    {
        $node = $this->dom->createElement('obra');
        $parent->appendChild($node);

        $this->addChildOptional($node, 'inscImobFisc', $obra->inscimobfisc ?? null);
        $this->addChildOptional($node, 'cObra', $obra->cobra ?? null);
        $this->addChildOptional($node, 'cCIB', $obra->ccib ?? null);

        if (isset($obra->end)) {
            $end = $this->dom->createElement('end');
            $node->appendChild($end);
            $this->addChildOptional($end, 'CEP', $obra->end->cep ?? null);
            $this->addChildOptional($end, 'xLgr', $obra->end->xlgr ?? null);
            $this->addChildOptional($end, 'nro', $obra->end->nro ?? null);
            $this->addChildOptional($end, 'xCpl', $obra->end->xcpl ?? null);
            $this->addChildOptional($end, 'xBairro', $obra->end->xbairro ?? null);
        }
    }

    private function buildAtvEvento(\DOMElement $parent, stdClass $atv): void
    {
        $node = $this->dom->createElement('atvEvento');
        $parent->appendChild($node);

        $this->addChildOptional($node, 'xNome', $atv->xnome ?? null);
        $this->addChildOptional($node, 'dtIni', $atv->dtini ?? null);
        $this->addChildOptional($node, 'dtFim', $atv->dtfim ?? null);

        if (isset($atv->end)) {
            $end = $this->dom->createElement('end');
            $node->appendChild($end);
            $this->addChildOptional($end, 'CEP', $atv->end->cep ?? null);
            $this->addChildOptional($end, 'xLgr', $atv->end->xlgr ?? null);
            $this->addChildOptional($end, 'nro', $atv->end->nro ?? null);
            $this->addChildOptional($end, 'xBairro', $atv->end->xbairro ?? null);
        }
    }

    private function buildInfoCompl(\DOMElement $parent, stdClass $info): void
    {
        $hasContent = isset($info->iddoctec) || isset($info->docref) || isset($info->xped)
            || isset($info->gitemped) || isset($info->xinfcomp);

        if (!$hasContent) {
            return;
        }

        $node = $this->dom->createElement('infoCompl');
        $parent->appendChild($node);

        $this->addChildOptional($node, 'idDocTec', $info->iddoctec ?? null);
        $this->addChildOptional($node, 'docRef', $info->docref ?? null);
        $this->addChildOptional($node, 'xPed', $info->xped ?? null);

        if (isset($info->gitemped)) {
            $gItem = $this->dom->createElement('gItemPed');
            $node->appendChild($gItem);
            $this->addChild($gItem, 'xItemPed', $info->gitemped->xitemped, true);
        }

        $this->addChildOptional($node, 'xInfComp', $info->xinfcomp ?? null);
    }

    private function buildValores(\DOMElement $parent): void
    {
        $val = $this->std->infdps->valores;

        $valNode = $this->dom->createElement('valores');
        $parent->appendChild($valNode);

        $vServPrest = $this->dom->createElement('vServPrest');
        $valNode->appendChild($vServPrest);
        $this->addChildOptional($vServPrest, 'vReceb', $val->vservprest->vreceb ?? null);
        $this->addChild($vServPrest, 'vServ', $val->vservprest->vserv, true);

        $vDescIncond = $val->vdesccondincond->vdescincond ?? null;
        $vDescCond = $val->vdesccondincond->vdesccond ?? null;

        $temDescIncond = $vDescIncond !== null && $vDescIncond !== '' && $vDescIncond !== '0.00';
        $temDescCond = $vDescCond !== null && $vDescCond !== '' && $vDescCond !== '0.00';

        if ($temDescIncond || $temDescCond) {
            $desc = $this->dom->createElement('vDescCondIncond');
            $valNode->appendChild($desc);
            $this->addChildOptional($desc, 'vDescIncond', $temDescIncond ? $vDescIncond : null);
            $this->addChildOptional($desc, 'vDescCond', $temDescCond ? $vDescCond : null);
        }

        $this->buildTributacao($valNode, $val->trib);
    }

    private function buildTributacao(\DOMElement $parent, stdClass $trib): void
    {
        $tribNode = $this->dom->createElement('trib');
        $parent->appendChild($tribNode);

        $tribMun = $this->dom->createElement('tribMun');
        $tribNode->appendChild($tribMun);

        $this->addChild($tribMun, 'tribISSQN', $trib->tribmun->tribissqn, true);

        if (($trib->tribmun->tribissqn ?? null) == 2 && isset($trib->tribmun->tpimunidade)) {
            $this->addChild($tribMun, 'tpImunidade', $trib->tribmun->tpimunidade, true);
        }

        if (($trib->tribmun->tribissqn ?? null) == 3) {
            $this->addChild($tribMun, 'cPaisResult', $trib->tribmun->cpaisresult, true);
        }

        $this->addChildOptional($tribMun, 'tpRetISSQN', $trib->tribmun->tpretissqn ?? null);
        $this->addChildOptional($tribMun, 'pAliq', $trib->tribmun->paliq ?? null);

        if (isset($trib->tribfed)) {
            $this->buildTribFed($tribNode, $trib->tribfed);
        }

        $this->buildTotTrib($tribNode, $trib->tottrib);
    }

    private function buildTribFed(\DOMElement $parent, stdClass $tribfed): void
    {
        $tf = $this->dom->createElement('tribFed');
        $parent->appendChild($tf);

        if (isset($tribfed->piscofins)) {
            $pc = $tribfed->piscofins;
            $pcNode = $this->dom->createElement('piscofins');
            $tf->appendChild($pcNode);
            $this->addChild($pcNode, 'CST', $pc->cst, true);
            $this->addChildOptional($pcNode, 'vBCPisCofins', $pc->vbcpiscofins ?? null);
            $this->addChildOptional($pcNode, 'pAliqPis', $pc->paliqpis ?? null);
            $this->addChildOptional($pcNode, 'pAliqCofins', $pc->paliqcofins ?? null);
            $this->addChildOptional($pcNode, 'vPis', $pc->vpis ?? null);
            $this->addChildOptional($pcNode, 'vCofins', $pc->vcofins ?? null);
            $this->addChildOptional($pcNode, 'tpRetPisCofins', $pc->tpretpiscofins ?? null);
        }

        $this->addChildOptional($tf, 'vRetCP', $tribfed->vretcp ?? null);
        $this->addChildOptional($tf, 'vRetIRRF', $tribfed->vretirrf ?? null);
        $this->addChildOptional($tf, 'vRetCSLL', $tribfed->vretcsll ?? null);
    }

    private function buildTotTrib(\DOMElement $parent, stdClass $totTrib): void
    {
        $tt = $this->dom->createElement('totTrib');
        $parent->appendChild($tt);

        if (isset($totTrib->vtottrib)) {
            $vt = $this->dom->createElement('vTotTrib');
            $tt->appendChild($vt);
            $this->addChildOptional($vt, 'vTotTribFed', $totTrib->vtottrib->vtottribfed ?? null);
            $this->addChildOptional($vt, 'vTotTribEst', $totTrib->vtottrib->vtottribest ?? null);
            $this->addChildOptional($vt, 'vTotTribMun', $totTrib->vtottrib->vtottribmun ?? null);
        }

        if (isset($totTrib->ptottrib)) {
            $pt = $this->dom->createElement('pTotTrib');
            $tt->appendChild($pt);
            $this->addChildOptional($pt, 'pTotTribFed', $totTrib->ptottrib->ptottribfed ?? null);
            $this->addChildOptional($pt, 'pTotTribEst', $totTrib->ptottrib->ptottribest ?? null);
            $this->addChildOptional($pt, 'pTotTribMun', $totTrib->ptottrib->ptottribmun ?? null);
        }

        $this->addChildOptional($tt, 'indTotTrib', $totTrib->indtottrib ?? null);
        $this->addChildOptional($tt, 'pTotTribSN', $totTrib->ptottribsn ?? null);
    }

    private function buildIbscbs(\DOMElement $parent): void
    {
        $ibscbs = $this->std->infdps->ibscbs;

        $node = $this->dom->createElement('IBSCBS');
        $parent->appendChild($node);

        $this->addChild($node, 'finNFSe', $ibscbs->finnfse, true);
        $this->addChildOptional($node, 'indFinal', $ibscbs->indfinal ?? null);
        $this->addChild($node, 'cIndOp', $ibscbs->cindop, true);
        $this->addChildOptional($node, 'tpOper', $ibscbs->tpoper ?? null);
        $this->addChildOptional($node, 'tpEnteGov', $ibscbs->tpentegov ?? null);
        $this->addChild($node, 'indDest', $ibscbs->inddest, true);

        if (isset($ibscbs->dest)) {
            $this->buildIbscbsDest($node, $ibscbs->dest);
        }

        $this->buildIbscbsValores($node, $ibscbs->valores);
    }

    private function buildIbscbsDest(\DOMElement $parent, stdClass $dest): void
    {
        $destNode = $this->dom->createElement('dest');
        $parent->appendChild($destNode);

        $this->addChildOptional($destNode, 'CNPJ', $dest->cnpj ?? null);
        $this->addChildOptional($destNode, 'CPF', $dest->cpf ?? null);
        $this->addChildOptional($destNode, 'NIF', $dest->nif ?? null);
        $this->addChildOptional($destNode, 'cNaoNIF', $dest->cnaonif ?? null);
        $this->addChild($destNode, 'xNome', $dest->xnome, true);
        $this->addChildOptional($destNode, 'fone', $dest->fone ?? null);
        $this->addChildOptional($destNode, 'email', $dest->email ?? null);

        if (isset($dest->end)) {
            $this->buildEndereco($destNode, $dest->end);
        }
    }

    private function buildIbscbsValores(\DOMElement $parent, stdClass $valores): void
    {
        $valNode = $this->dom->createElement('valores');
        $parent->appendChild($valNode);

        $tribNode = $this->dom->createElement('trib');
        $valNode->appendChild($tribNode);

        $gIbscbs = $this->dom->createElement('gIBSCBS');
        $tribNode->appendChild($gIbscbs);
        $this->addChild($gIbscbs, 'CST', $valores->trib->gibscbs->cst, true);
        $this->addChild($gIbscbs, 'cClassTrib', $valores->trib->gibscbs->cclasstrib, true);
        $this->addChildOptional($gIbscbs, 'cCredPres', $valores->trib->gibscbs->ccredpres ?? null);

        if (isset($valores->trib->gtribregular)) {
            $gReg = $this->dom->createElement('gTribRegular');
            $gIbscbs->appendChild($gReg);
            $this->addChild($gReg, 'CSTReg', $valores->trib->gtribregular->cstreg, true);
            $this->addChild($gReg, 'cClassTribReg', $valores->trib->gtribregular->cclasstribreg, true);
        }

        if (isset($valores->trib->gdif)) {
            $gDif = $this->dom->createElement('gDif');
            $gIbscbs->appendChild($gDif);
            $this->addChild($gDif, 'pDifUF', $valores->trib->gdif->pdifuf, true);
            $this->addChild($gDif, 'pDifMun', $valores->trib->gdif->pdifmun, true);
            $this->addChild($gDif, 'pDifCBS', $valores->trib->gdif->pdifcbs, true);
        }
    }

    public function setFormatOutput(bool $formatOutput): void
    {
        $this->dom->formatOutput = $formatOutput;
    }

    public function setStd(stdClass $std): void
    {
        $this->init($std);
    }

    public static function propertiesToLower(stdClass $data): stdClass
    {
        $clone = new stdClass();
        foreach (get_object_vars($data) as $key => $value) {
            $clone->{strtolower($key)} = $value instanceof stdClass
                ? self::propertiesToLower($value)
                : $value;
        }
        return $clone;
    }

    public function getDpsId(): string
    {
        return $this->dpsId;
    }

    public function getEventoId(): string
    {
        return $this->preId;
    }

    private function generateId(): string
    {
        $id = 'DPS';
        $id .= substr($this->std->infdps->clocemi, 0, 7);
        $id .= isset($this->std->infdps->prest->cnpj) ? '2' : '1';
        $inscricao = $this->std->infdps->prest->cnpj ?? $this->std->infdps->prest->cpf;
        $id .= str_pad($inscricao, 14, '0', STR_PAD_LEFT);
        $id .= str_pad((string)$this->std->infdps->serie, 5, '0', STR_PAD_LEFT);
        $id .= str_pad((string)$this->std->infdps->ndps, 15, '0', STR_PAD_LEFT);

        $this->dpsId = $id;
        return $id;
    }

    private function generatePre(): string
    {
        $this->preId = 'PRE'
            . $this->std->infpedreg->chnfse
            . $this->codigoEvento();

        return $this->preId;
    }

    private function codigoEvento(): string
    {
        return match (true) {
            isset($this->std->infpedreg->e101101) => '101101',
            isset($this->std->infpedreg->e105102) => '105102',
            default => '000000',
        };
    }

    private function addChild(\DOMElement $parent, string $name, mixed $value, bool $force = false): void
    {
        $this->dom->addChild($parent, $name, $value, $force);
    }

    private function addChildOptional(\DOMElement $parent, string $name, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $this->dom->addChild($parent, $name, $value, false);
        }
    }
}
