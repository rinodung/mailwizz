<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * IpLocationServicesList
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

class IpLocationServicesList extends FormModel
{
    /**
     * @return CArrayDataProvider
     */
    public function getDataProvider(): CArrayDataProvider
    {
        $registeredServices = (array)hooks()->applyFilters('backend_ip_location_services_display_list', []);
        if (empty($registeredServices)) {
            return new CArrayDataProvider([]);
        }

        $validRegisteredServices = $sortOrder = [];
        /** @var array $service */
        foreach ($registeredServices as $service) {
            if (!isset($service['id'], $service['name'], $service['description'], $service['status'], $service['sort_order'])) {
                continue;
            }
            $sortOrder[] = (int)$service['sort_order'];
            $validRegisteredServices[] = $service;
        }

        if (empty($validRegisteredServices)) {
            return new CArrayDataProvider([]);
        }

        array_multisort($sortOrder, SORT_NUMERIC, $validRegisteredServices);

        /**
         * @var int $index
         * @var array $service
         */
        foreach ($validRegisteredServices as $index => $service) {
            $service['name'] = html_encode((string)$service['name']);
            if (!empty($service['page_url'])) {
                $service['name'] = CHtml::link($service['name'], $service['page_url']);
            }
            $validRegisteredServices[$index] = [
                'id'            => $service['id'],
                'name'          => $service['name'],
                'description'   => $service['description'],
                'status'        => ucfirst(t('app', (string)$service['status'])),
                'sort_order'    => (int)$service['sort_order'],
                'page_url'      => $service['page_url'] ?? null,
            ];
        }

        return new CArrayDataProvider($validRegisteredServices);
    }
}
