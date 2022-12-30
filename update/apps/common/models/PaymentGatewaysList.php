<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaymentGatewaysList
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

class PaymentGatewaysList extends FormModel
{
    /**
     * @return CArrayDataProvider
     */
    public function getDataProvider(): CArrayDataProvider
    {
        $registeredGateways = (array)hooks()->applyFilters('backend_payment_gateways_display_list', []);
        if (empty($registeredGateways)) {
            return new CArrayDataProvider([]);
        }

        $validRegisteredGateways = $sortOrder = [];

        /** @var array $gateway */
        foreach ($registeredGateways as $gateway) {
            if (!isset($gateway['id'], $gateway['name'], $gateway['description'], $gateway['status'], $gateway['sort_order'])) {
                continue;
            }
            $sortOrder[] = (int)$gateway['sort_order'];
            $validRegisteredGateways[] = $gateway;
        }

        if (empty($validRegisteredGateways)) {
            return new CArrayDataProvider([]);
        }

        array_multisort($sortOrder, SORT_NUMERIC, $validRegisteredGateways);

        /**
         * @var int $index
         * @var array $gateway
         */
        foreach ($validRegisteredGateways as $index => $gateway) {
            $gateway['name'] = html_encode((string)$gateway['name']);
            if (!empty($gateway['page_url'])) {
                $gateway['name'] = CHtml::link($gateway['name'], $gateway['page_url']);
            }
            $validRegisteredGateways[$index] = [
                'id'            => $gateway['id'],
                'name'          => $gateway['name'],
                'description'   => $gateway['description'],
                'status'        => ucfirst(t('app', $gateway['status'])),
                'sort_order'    => (int)$gateway['sort_order'],
                'page_url'      => $gateway['page_url'] ?? null,
            ];
        }

        return new CArrayDataProvider($validRegisteredGateways);
    }
}
