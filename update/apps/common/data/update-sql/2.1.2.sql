--
-- Update sql for MailWizz EMA from version 2.1.1 to 2.1.2
--

--
-- Alter `campaign_option` table
--

ALTER TABLE `campaign_option` ADD `email_stats_delay_days` INT(11) NOT NULL DEFAULT '0' AFTER `email_stats_sent`;
