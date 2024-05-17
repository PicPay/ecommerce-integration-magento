<?php

/**
 *
 *
 *
 * @category    PicPay
 * @package     PicPay_Checkout
 */

namespace PicPay\Checkout\Controller\Installments;

use PicPay\Checkout\Helper\Data as HelperData;
use PicPay\Checkout\Helper\Installments;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Retrieve extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /** @var HelperData */
    protected $helperData;

    /** @var Json */
    protected $json;

    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var Session */
    protected $checkoutSession;

    /** @var Installments */
    private $helperInstallments;

    /** @var SessionManagerInterface */
    protected $session;

    public function __construct(
        Context $context,
        Json $json,
        Session $checkoutSession,
        SessionManagerInterface $session,
        JsonFactory $resultJsonFactory,
        Installments $helperInstallments,
        HelperData $helperData
    ) {
        $this->json = $json;
        $this->checkoutSession = $checkoutSession;
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helperData = $helperData;
        $this->helperInstallments = $helperInstallments;

        parent::__construct($context);
    }

    public function execute()
    {
        //Salvar todas as formas de pagamento disponíveis
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(401);

        try{
            $content = $this->getRequest()->getContent();
            $bodyParams = ($content) ? $this->json->unserialize($content) : [];
            $ccType = $bodyParams['cc_type'] ?? '';

            $result->setJsonData($this->json->serialize($this->getInstallments($ccType)));
            $result->setHttpResponseCode(200);
        } catch (\Exception $e) {
            $result->setHttpResponseCode(500);
        }

        return $result;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getInstallments(string $ccType): array
    {
        $this->session->setPicPayCcType($ccType);
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        return $this->helperInstallments->getAllInstallments($grandTotal);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHttpResponseCode(403);
        return new InvalidRequestException(
            $result
        );
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
