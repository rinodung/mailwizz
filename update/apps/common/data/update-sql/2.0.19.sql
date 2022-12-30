--
-- Update sql for MailWizz EMA from version 2.0.18 to 2.0.19
--

--
-- Make sure we disable any php-mail server type since we don't use it anymore
-- 
UPDATE `delivery_server` SET `status` = 'disabled' WHERE `type` = 'php-mail';