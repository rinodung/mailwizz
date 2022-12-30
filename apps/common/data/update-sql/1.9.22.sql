--
-- Update sql for MailWizz EMA from version 1.9.21 to 1.9.22
--

ALTER TABLE `campaign_option`
    ADD `cronjob_rescheduled` ENUM('no','yes') NOT NULL DEFAULT 'no' AFTER `cronjob_runs_counter`;

ALTER TABLE `transactional_email`
    ADD `fallback_system_servers` ENUM('no','yes') NOT NULL DEFAULT 'no' AFTER `send_at`;