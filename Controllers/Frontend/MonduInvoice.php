<?php

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Document\Document;

class Shopware_Controllers_Frontend_MonduInvoice extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    public function invoiceAction()
    {
        $hash = $this->request->getParam('hash');

        //remove . and / from hash string ( it's mostly a redundancy because the query below will return null in that case )
        $hash = str_replace(['.', '/'], '', $hash);
        $documentId = $this->request->getParam('documentId');
        $qb = $this->getModelManager()->createQueryBuilder();
        $qb->select('document')
            ->from(Document::class, 'document')
            ->innerJoin('document.order', 'e_order')
            ->innerJoin('document.type', 'type')
            ->where(
                $qb->expr()->eq('document.documentId', ':doc_id'),
                $qb->expr()->eq('document.hash', ':hash'),
                $qb->expr()->eq('e_order.id', ':order_id'),
                $qb->expr()->eq('type.key', ':type')
            )
            ->setParameter('doc_id', $documentId)
            ->setParameter('hash', $hash)
            ->setParameter('order_id', $this->request->getParam('orderId'))
            ->setParameter('type', $this->request->getParam('type'));
        $document = $qb->getQuery()->getOneOrNullResult();

        // Return 404 if document was not found in database
        if (!$document) {
            $this->Response()->setHttpResponseCode(404);
            return;
        }

        $fs = $this->container->get('shopware.filesystem.private');

        // Return 404 if document was not found in the file system
        if(!$fs->has("documents/$hash.pdf")) {
            $this->Response()->setHttpResponseCode(404);
            return;
        }

        $file = $fs->read("documents/$hash.pdf");
        $size = $fs->getSize("documents/$hash.pdf");

        $this->Response()->setHeader('Content-Type', 'application/octet-stream', true);
        $this->Response()->setHeader('Content-Type', 'application/pdf', true);
        $this->Response()->setHeader('Content-Disposition', 'inline; filename = "' . $documentId . '.pdf' . '"');
        $this->Response()->setHeader('Content-Transfer-Encoding', 'binary');
        $this->Response()->setHeader('Content-Length', $size);
        $this->Response()->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');
        $this->Response()->setHeader('Pragma', 'public');
        $this->Response()->setHeader('Accept-Ranges', 'bytes');
        $this->Response()->setBody($file);
    }

    public function preDispatch()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
    }

    public function getWhitelistedCSRFActions()
    {
        return ['invoice'];
    }
}
