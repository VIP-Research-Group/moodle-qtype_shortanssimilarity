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
 * Adhoc task class for Short Answer Similarity.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_shortanssimilarity;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/question/type/shortanssimilarity/lib.php');

/**
 * Adhoc task that calcualtes similarity between two multi-sentences.
 *
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calculator extends \core\task\adhoc_task {

    /**
     * Get the component name.
     *
     * @return  string
     */
    public function get_component() {
        return 'qtype_shortanssimilarity';
    }

    /**
     * Called to execute this task.
     *
     * @return  float
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        $contents = qtype_shortanssimilarity_call_bridge($data);

        $params = [
            'questionid' => $data->id,
            'userid' => $data->userid,
            'result' => 0,
            'finished' => 0,
            'response' => hash('md5', $data->target),
        ];
        $query = "SELECT * FROM {qtype_shortanssim_attempt}
                           WHERE questionid = :questionid
                             AND userid = :userid
                             AND result = 0
                             AND finished = 0
                             AND " . $DB->sql_compare_text('response') . " = " . $DB->sql_compare_text(':response');
        $attempts = $DB->get_records_sql($query, $params);
        $keys = array_keys($attempts);
        $attempt = $attempts[$keys[count($keys) - 1]];

        $attempt->result = $contents->similarity;
        $attempt->finished = 1;
        $DB->update_record('qtype_shortanssim_attempt', $attempt);

        return true;
    }
}
