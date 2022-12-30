<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TwigHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.9
 */

class TwigHelper
{
    /**
     * @var Twig\Environment
     */
    protected static $instance;

    /**
     * @return Twig\Environment
     */
    public static function getInstance(): Twig\Environment
    {
        if (self::$instance === null) {
            self::$instance = self::createInstance();
        }
        return self::$instance;
    }

    /**
     * @return Twig\Environment
     */
    public static function createInstance(): Twig\Environment
    {
        $instance = new Twig\Environment(new Twig\Loader\ArrayLoader());

        /** @var Twig\Environment $instance */
        $instance = hooks()->applyFilters('twig_create_instance', $instance);

        return $instance;
    }
}
