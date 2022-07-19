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
 * short answer similarity question definition class.
 *
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivatava - VIP Research Group (ysrivast@ualberta.ca
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/shortanssimilarity/classes/calculator.php');
require_once($CFG->dirroot . '/question/type/questionbase.php');
require_once('lib.php');

/**
 * Question type class for the short answer similarity question.
 *
 * @copyright  2021 Yash Srivatava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_shortanssimilarity_question extends question_with_responses implements question_automatically_gradable {

    /**
     * Set the behaviour of the question as manual graded.
     *
     * @param question_attempt $qa - The question attempt.
     * @param string $preferredbehaviour - The default question behaviour.
     * @return question_behaviour
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        global $DB;

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));

        if ($question->manual_grading == 1) {
            return question_engine::make_behaviour('manualgraded', $qa, $preferredbehaviour);
        } else {
            return question_engine::make_archetypal_behaviour($preferredbehaviour, $qa);
        }
    }

    /**
     * Data to be included in the form submission when a student submits the question
     * in it's current state.
     *
     * @return array
     */
    public function get_expected_data() {
        return array('answer' => PARAM_RAW);
    }

    /**
     * Returns the renderer.
     * @param moodle_page $page The page we are outputting to.
     * @return qtype_essay_format_renderer_base
     */
    public function get_format_renderer(moodle_page $page) {
        return $page->get_renderer('qtype_essay', 'FORMAT_PLAIN');
    }

    /**
     * Start a new attempt at this question, storing any information that will
     * be needed later in the step.
     *
     * This is where the question can do any initialisation required on a
     * per-attempt basis. For example, this is where the multiple choice
     * question type randomly shuffles the choices (if that option is set).
     *
     * Any information about how the question has been set up for this attempt
     * should be stored in the $step, by calling $step->set_qt_var(...).
     *
     * @param question_attempt_step $step - The first step of the {@link question_attempt}
     *      being started. Can be used to store state.
     * @param int $variant - Which variant of this question to start. Will be between
     *      1 and {@link get_num_variants()} inclusive.
     */
    public function start_attempt(question_attempt_step $step, $variant) {
        global $DB;

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));
        $attempt = [
            'questionid' => $question->id,
            'userid' => $step->get_user_id(),
            'result' => 0,
            'queued' => 0,
            'finished' => 0,
            'response' => '',
        ];
        $DB->insert_record('qtype_shortanssim_attempt', $attempt);
    }

    /**
     * Produce a plain text summary of a response.
     *
     * @param array $response - The question's response.
     * @return string
     * */
    public function summarise_response(array $response) {
        $output = null;

        if (isset($response['answer'])) {
            $output .= get_string('summarize_repsponse_valid', 'qtype_shortanssimilarity') . $response['answer'];
        } else {
            $output .= get_string('summarize_repsponse_invalid', 'qtype_shortanssimilarity');
        }

        return $output;
    }

    /**
     * Used to un-summarize a response.
     * @param string $summary Contains the summary.
     * @return array
     */
    public function un_summarise_response(string $summary) {
        if (!empty($summary)) {
            return array('answer' => text_to_html($summary));
        } else {
            return array();
        }
    }

    /**
     * Used to capture matching answers.
     * @param array $response Contains the response.
     * @return array
     */
    public function get_matching_answer(array $response) {
        global $DB;

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));
        $fraction = $question->result;

        return array('fraction' => $fraction);
    }

    /**
     * Is this question manually or automatically graded?
     * @return boolean
     */
    public function using_chron() {
        global $DB;

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));

        if ($question->manual_grading == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the grade for this question.
     *
     * @param array $response - The student's response.
     * @param int $userid - The user ID.
     * @return float
     */
    public function get_grade($response, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));

        $params = [
            'questionid' => $question->id,
            'userid' => $userid,
            'finished' => 1,
            'response' => hash('md5', $response),
        ];
        $query = "SELECT * FROM {qtype_shortanssim_attempt}
                           WHERE questionid = :questionid
                             AND userid = :userid
                             AND finished = 1
                             AND " . $DB->sql_compare_text('response') . " = " . $DB->sql_compare_text(':response') .
                            "ORDER BY id";
        $attempts = $DB->get_records_sql($query, $params);

        $keys = array_keys($attempts);
        $attempt = $attempts[$keys[count($keys) - 1]];

        return $attempt->result * $this->defaultmark;
    }

    /**
     * Determines if the response is complete.
     * @param array $response Contains the response.
     * @return boolean
     */
    public function is_complete_response(array $response) {
        global $DB, $USER;

        if (array_key_exists('answer', $response) && ($response['answer'] !== '')) {
            $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));

            $params = array(
                'questionid' => $question->id,
                'userid' => $USER->id,
            );
            $attempts = $DB->get_records('qtype_shortanssim_attempt', $params);
            $keys = array_keys($attempts);
            $attempt = $attempts[$keys[count($keys) - 1]];

            if ($question->manual_grading == 1) {
                if (!$attempt->queued) {
                    $this->calculate_simularity($question, $response, $USER->id, false);

                } else if ($attempt->response != hash('md5', $response['answer'])) {
                    $this->calculate_simularity($question, $response, $USER->id, true);
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks or validates the response.
     * @param array $response Contains the response.
     * @return boolean
     */
    public function get_validation_error(array $response) {
        if (!$this->is_complete_response($response)) {
            return get_string('validation_error_no_response', 'qtype_shortanssimilarity');
        } else {
            return get_string('empty_string', 'qtype_shortanssimilarity');
        }

        return get_string('validation_error_error', 'qtype_shortanssimilarity');
    }

    /**
     * Checks to see if this question has been marked yet.
     *
     * @param array $response - The student's response.
     * @param int $userid - The user ID.
     * @return boolean
     */
    public function is_completed_marking($response, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));
        $params = array(
            'questionid' => $question->id,
            'userid' => $userid,
            'response' => hash('md5', $response)
        );
        $query = "SELECT * FROM {qtype_shortanssim_attempt}
                           WHERE questionid = :questionid
                             AND userid = :userid
                             AND " . $DB->sql_compare_text('response') . " = " . $DB->sql_compare_text(':response') .
                            "ORDER BY id";
        $attempts = $DB->get_records_sql($query, $params);
        if (!count($attempts)) {
            return false;
        }

        $keys = array_keys($attempts);
        $attempt = $attempts[$keys[count($keys) - 1]];

        if (!$attempt->finished) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * If you are moving from viewing one question to another this will
     * discard the processing if the answer has not changed. If you don't
     * use this method it will constantly generate new question steps and
     * the question will be repeatedly set to incomplete. This is a comparison of
     * the equality of two arrays.
     * Comment from base class:
     *
     * Use by many of the behaviours to determine whether the student's
     * response has changed. This is normally used to determine that a new set
     * of responses can safely be discarded.
     *
     * @param array $prevresponse the responses previously recorded for this question,
     *      as returned by {@link question_attempt_step::get_qt_data()}
     * @param array $newresponse the new responses, in the same format.
     * @return bool whether the two sets of responses are the same - that is
     *      whether the new set of responses can safely be discarded.
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        if (array_key_exists('answer', $prevresponse) && $prevresponse['answer'] !== '') {
            $value1 = (string) $prevresponse['answer'];
        } else {
            $value1 = '';
        }

        if (array_key_exists('answer', $newresponse) && $newresponse['answer'] !== '') {
            $value2 = (string) $newresponse['answer'];
        } else {
            $value2 = '';
        }

        return ($value1 === $value2 || question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer'));
    }

    /**
     * Called to get an answer that
     * contains the a response that would get full marks.
     * used in preview mode. If this doesn't return a
     * correct value the button labeled "Fill in correct response"
     * in the preview form will not work. This value gets written
     * into the rightanswer field of the question_attempts table
     * when a quiz containing this question starts.
     *
     * @return question_answer
     */
    public function get_correct_response() {
        return null;
    }

    /**
     * Called to queue the similarity calculation for manually marked questions.
     *
     * @param stdClass $question - The question.
     * @param array $response - The student's response to the question.
     * @param int $userid - The student's ID.
     * @param boolean $redo - The question may have been answered, but not submitted.
     */
    public function calculate_simularity($question, $response, $userid, $redo) {
        global $DB, $COURSE;

        $task = new qtype_shortanssimilarity\calculator();
        $task->set_custom_data(array(
            'key' => $question->key_text,
            'target' => $response['answer'],
            'userid' => $userid,
            'language' => $question->item_language,
            'id' => $question->id,
        ));
        \core\task\manager::queue_adhoc_task($task);

        $params = [
            'questionid' => $question->id,
            'userid' => $userid,
        ];
        if (!$redo) {
            $params['result'] = 0;
            $params['queued'] = 0;
            $params['finished'] = 0;
        } else {
            $params['queued'] = 1;
        }

        $attempts = $DB->get_records('qtype_shortanssim_attempt', $params);
        $keys = array_keys($attempts);
        $attempt = $attempts[$keys[count($keys) - 1]];

        $attempt->result = 0;
        $attempt->queued = 1;
        $attempt->finished = 0;
        $attempt->response = hash('md5', $response['answer']);
        $DB->update_record('qtype_shortanssim_attempt', $attempt);
    }

    /**
     * Called when automatically grading a response. This function passes the
     * question key text and student answer to the web service for similarity
     * calculation, then stores and returns the result.
     *
     * @param array $response responses, as returned by
     *      {@link question_attempt_step::get_qt_data()}.
     * @return array (number, integer) the fraction, and the state.
     */
    public function grade_response(array $response) {
        global $DB, $USER;

        $question = $DB->get_record('qtype_shortanssimilarity', array('questionid' => $this->id));

        $params = [
            'questionid' => $question->id,
            'userid' => $USER->id,
            'result' => 0,
            'queued' => 0,
            'finished' => 0,
        ];
        $attempts = $DB->get_records('qtype_shortanssim_attempt', $params);

        if (!count($attempts)) { // Student may have "checked" the answer already.
            unset($params['result']);
            unset($params['finished']);
            $params['queued'] = 1;
            $attempts = $DB->get_records('qtype_shortanssim_attempt', $params);
        }
        $keys = array_keys($attempts);
        $attempt = $attempts[$keys[count($keys) - 1]];

        // If checked answer has not changed, no need to redo similarity calculation.
        if ($attempt->finished && hash('md5', $response['answer']) == $attempt->response) {
            return array((double) $attempt->result,
                         question_state::graded_state_for_fraction((double) $attempt->result));
        }

        $attempt->queued = 1;
        $attempt->response = hash('md5', $response['answer']);
        $DB->update_record('qtype_shortanssim_attempt', $attempt);

        $contents = qtype_shortanssimilarity_call_bridge($question->key_text, $response['answer'], $question->item_language);

        $attempt->result = $contents->similarity;
        $attempt->finished = 1;
        $DB->update_record('qtype_shortanssim_attempt', $attempt);

        return array((double) $contents->similarity,
            question_state::graded_state_for_fraction((double) $contents->similarity));
    }

    /**
     * Get one of the question hints. The question_attempt is passed in case
     * the question type wants to do something complex. For example, the
     * multiple choice with multiple responses question type will turn off most
     * of the hint options if the student has selected too many opitions.
     * @param int $hintnumber Which hint to display. Indexed starting from 0
     * @param question_attempt $qa The question_attempt.
     */
    public function get_hint($hintnumber, question_attempt $qa) {
        return null;
    }

    /**
     * Generate a brief, plain-text, summary of the correct answer to this question.
     * This is used by various reports, and can also be useful when testing.
     * This method will return null if such a summary is not possible, or
     * inappropriate.
     * @return string|null a plain text summary of the right answer to this question.
     */
    public function get_right_answer_summary() {
        return '';
    }

    /**
     * Implemented to conform to interface requirements.
     * @param array $response The response to the question.
     * @return boolean
     */
    public function is_gradable_response(array $response) {

        if (array_key_exists('answer', $response) && ($response['answer'] !== '')) {
            return true;
        } else {
            return false;
        }
    }
}
