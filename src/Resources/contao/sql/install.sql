-- ============================================================
-- LUMAS Antispam Bundle
-- SQL schema for Contao Install Tool
-- ============================================================

-- ------------------------------------------------------------
-- Table: tl_lumas_antispam_ip_block
-- ------------------------------------------------------------
CREATE TABLE `tl_lumas_antispam_ip_block` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `ip_address` varchar(45) NOT NULL default '',
  `block_count` int(10) unsigned NOT NULL default '0',
  `reputation_score` int(10) unsigned NOT NULL default '0',
  `ip_block_ttl` int(10) unsigned NOT NULL default '24',
  `is_whitelisted` char(1) NOT NULL default '',
  `is_hard_blocked` char(1) NOT NULL default '',
  `is_permanent` char(1) NOT NULL default '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: tl_lumas_antispam_log
-- ------------------------------------------------------------
CREATE TABLE `tl_lumas_antispam_log` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `ip_address` varchar(45) NOT NULL default '',
  `reason` varchar(64) NOT NULL default '',
  `form_alias` varchar(128) NOT NULL default '',
  `details` text NULL,
  PRIMARY KEY (`id`),
  KEY `tstamp` (`tstamp`),
  KEY `ip_address` (`ip_address`),
  KEY `reason` (`reason`),
  KEY `form_alias` (`form_alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Extend core table: tl_form
-- (Per-form overrides, NULL = use root/default)
-- ------------------------------------------------------------
ALTER TABLE `tl_form`
  ADD COLUMN `lumas_antispam_enable` char(1) NOT NULL default '',
  ADD COLUMN `lumas_antispam_ip_block` char(1) NOT NULL default '',
  ADD COLUMN `lumas_antispam_language` varchar(2) NOT NULL default '',
  ADD COLUMN `lumas_antispam_minDelay` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_blockTime` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_stopwordCount` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_maxLinks` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_minLen` int(10) unsigned NULL;

-- ------------------------------------------------------------
-- Extend core table: tl_page (root pages)
-- (Defaults, NULL = fallback to internal defaults)
-- ------------------------------------------------------------
ALTER TABLE `tl_page`
  ADD COLUMN `lumas_antispam_ip_block` char(1) NOT NULL default '',
  ADD COLUMN `lumas_antispam_language` varchar(2) NOT NULL default '',
  ADD COLUMN `lumas_antispam_ip_block_ttl` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_minDelay` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_blockTime` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_stopwordCount` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_maxLinks` int(10) unsigned NULL,
  ADD COLUMN `lumas_antispam_minLen` int(10) unsigned NULL;
