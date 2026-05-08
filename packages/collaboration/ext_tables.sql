# Define table and fields for sys_event_messages
CREATE TABLE sys_event_messages (
    timestamp int(10) unsigned DEFAULT '0' NOT NULL,
    owner_id int(10) unsigned DEFAULT '0' NOT NULL,
    owner_name varchar(255) NOT NULL,
    users_to_inform varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    message json NOT NULL
);

CREATE TABLE sys_collaboration_event (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    timestamp int(10) unsigned DEFAULT '0' NOT NULL,
    user_id INT NOT NULL,
    page_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    payload JSON NOT NULL
);

CREATE TABLE tx_collaboration_presence (
    uid int(11) unsigned NOT NULL auto_increment,
    userid int(11) unsigned DEFAULT '0' NOT NULL,
    session_id varchar(64) DEFAULT '' NOT NULL,
    page_id int(11) unsigned DEFAULT '0' NOT NULL,
    module varchar(64) DEFAULT '' NOT NULL,
    record_table varchar(64) DEFAULT '' NOT NULL,
    record_uid int(11) unsigned DEFAULT '0' NOT NULL,
    field varchar(64) DEFAULT '' NOT NULL,
    first_seen int(11) unsigned DEFAULT '0' NOT NULL,
    last_seen int(11) unsigned DEFAULT '0' NOT NULL,
    PRIMARY KEY (uid),
    UNIQUE KEY userid_session (userid, session_id),
    KEY page_lookup (page_id, last_seen),
    KEY record_lookup (record_table, record_uid, last_seen),
    KEY last_seen_idx (last_seen)
);

# Extend table sys_note
CREATE TABLE sys_note (
    assigned_name varchar(255) NOT NULL
);
