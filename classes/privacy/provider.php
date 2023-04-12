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
 * Privacy Subsystem implementation for question type Short Answer Similarity.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace qtype_shortanssimilarity\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy Subsystem for question type Short Answer Similarity.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns information about how qtype_shortanssimilarity stores its data.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_external_location_link(
            'ws_nlp_web_service',
            [
                'answer' => 'privacy:metadata:ws_nlp_web_service:answer',
            ],
            'privacy:metadata:ws_nlp_web_service'
        );

        $collection->add_database_table(
            'qtype_shortanssimilarity',
            [
                'questionid' => 'privacy:metadata:qtype_shortanssimilarity:questionid',
                'key_text' => 'privacy:metadata:qtype_shortanssimilarity:key_text',
                'item_language' => 'privacy:metadata:qtype_shortanssimilarity:item_language',
                'result' => 'privacy:metadata:qtype_shortanssimilarity:result',
                'finished' => 'privacy:metadata:qtype_shortanssimilarity:finished',
                'manual_grading' => 'privacy:metadata:qtype_shortanssimilarity:manual_grading',
                'maxbpm' => 'privacy:metadata:qtype_shortanssimilarity:maxbpm',
                'ngrampos' => 'privacy:metadata:qtype_shortanssimilarity:ngrampos',
                'canonical' => 'privacy:metadata:qtype_shortanssimilarity:canonical',
            ],
            'privacy:metadata:shortanssimilarity'
        );

        $collection->add_database_table(
            'qtype_shortanssim_attempt',
            [
                'questionid' => 'privacy:metadata:qtype_shortanssimilarity:questionid',
                'userid' => 'privacy:metadata:qtype_shortanssimilarity:userid',
                'result' => 'privacy:metadata:qtype_shortanssimilarity:result',
                'queued' => 'privacy:metadata:qtype_shortanssimilarity:queued',
                'finished' => 'privacy:metadata:qtype_shortanssimilarity:finished',
                'response' => 'privacy:metadata:qtype_shortanssimilarity:response',
            ],
            'privacy:metadata:shortanssim_attempt'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new \core_privacy\local\request\contextlist();

        // The qtype_shortanssimilarity data is associated at the module context level, so retrieve the user's context id.
        $sql = "SELECT id
                  FROM {context}
                 WHERE contextlevel = :context
                   AND instanceid = :userid
              GROUP BY id";

        $params = [
            'context' => CONTEXT_MODULE,
            'userid' => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = ['contextid' => $context->id];

        $sql = "SELECT distinct(userid)
                  FROM {qtype_shortanssim_attempt}
              ORDER BY userid";

        $userlist->add_from_sql('userid', $sql, $params);
        return;
    }

    /**
     * Export all user data for the specified user using the Module context level.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // If the user has block_behaviour data, then only the Course context should be present.
        $contexts = $contextlist->get_contexts();
        if (count($contexts) == 0) {
            return;
        }

        $seen = [];

        // Export data for each user.
        foreach ($contexts as $context) {

            // Sanity check that context is at the Course context level.
            if ($context->contextlevel !== CONTEXT_MODULE) {
                return;
            }

            // Don't process a user id more than once.
            if (isset($seen[$context->instanceid])) {
                continue;
            }

            $seen[$context->instanceid] = 1;
            $params = ['userid' => $context->instanceid];

            $data = (object) $DB->get_records('qtype_shortanssim_attempt', $params);
            writer::with_context($context)->export_data([], $data);
        }
        return;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Sanity check that context is at the Module context level.
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $params = ['idvalue' => 0];
        $cond = 'id > :idvalue';

        $DB->delete_records_select('qtype_shortanssim_attempt', $cond, $params);
        return;
    }

    /**
     * Delete all user data for the specified user.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        // If the user has block_behaviour data, then only the Course context should be present.
        $contexts = $contextlist->get_contexts();
        if (count($contexts) == 0) {
            return;
        }
        $context = reset($contexts);

        // Sanity check that context is at the Module context level.
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $params = ['userid' => $context->instanceid];
        $DB->delete_records('qtype_shortanssim_attempt', $params);

        return;
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {

            $params = ['userid' => $userid];
            $DB->delete_records('qtype_shortanssim_attempt', $params);
        }
        return;
    }
}
