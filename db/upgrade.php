<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Short answer similarity question type upgrade code.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade code for the shorta answer similarity question type.
 * A selection of things you might want to do when upgrading
 * to a new version. This file is generally not needed for
 * the first release of a question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_shortanssimilarity_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022063000) {

        // Define field item_language to be dropped from qtype_shortanssim_attempt.
        $table = new xmldb_table('qtype_shortanssim_attempt');
        $field = new xmldb_field('item_language');

        // Conditionally launch drop field item_language.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('manual_grading');

        // Conditionally launch drop field manual_grading.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'questionid');

        // Conditionally launch add field userid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Shortanssimilarity savepoint reached.
        upgrade_plugin_savepoint(true, 2022063000, 'qtype', 'shortanssimilarity');
    }

    if ($oldversion < 2022070100) {

        // Define field item_language to be dropped from qtype_shortanssim_attempt.
        $table = new xmldb_table('qtype_shortanssim_attempt');
        $field = new xmldb_field('queued', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'result');

        // Conditionally launch add field queued.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Shortanssimilarity savepoint reached.
        upgrade_plugin_savepoint(true, 2022070100, 'qtype', 'shortanssimilarity');
    }

    if ($oldversion < 2022070600) {

        // Define field item_language to be dropped from qtype_shortanssim_attempt.
        $table = new xmldb_table('qtype_shortanssim_attempt');
        $field = new xmldb_field('response');

        // Conditionally launch drop field response.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('response', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null, 'finished');

        // Conditionally launch add field response.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Shortanssimilarity savepoint reached.
        upgrade_plugin_savepoint(true, 2022070600, 'qtype', 'shortanssimilarity');
    }

    return true;
}
