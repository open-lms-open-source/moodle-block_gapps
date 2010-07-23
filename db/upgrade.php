<?php
/**
 * Upgrade routine
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package blocks/helloworld
 **/

function xmldb_block_helloworld_upgrade($oldversion=0) {
    $result = true;

    if ($result && $oldversion < 2010022400) {

    /// Define table block_helloworld to be created
        $table = new XMLDBTable('block_gapps');

    /// Adding fields to table block_helloworld
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, null, null, null, null, null);
        $table->addFieldInfo('foo', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);
        $table->addFieldInfo('bar', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);

    /// Adding keys to table block_helloworld
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for block_helloworld
        $result = $result && create_table($table);
    }

    return $result;
}

?>