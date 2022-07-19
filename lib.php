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
 * Serve question type files
 *
 * @since      2.0
 * @package    qtype_shortanssimilarity
 * @copyright  2021 Yash Srivastava - VIP Research Group (ysrivast@ualberta.ca)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Checks file access for short answer similarity questions.
 * @package  qtype_shortanssimilarity
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 */
function qtype_shortanssimilarity_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_shortanssimilarity', $filearea, $args, $forcedownload, $options);
}

/**
 * This function calls the word and sentence natural language processing service
 * to get the similarity between the question's answer and the student's response.
 *
 * @param string $answer - The teacher's answer to the question.
 * @param string $response - The student's response to the question.
 * @param string $language - The language setting for this question.
 * @return object
 */
function qtype_shortanssimilarity_call_bridge($answer, $response, $language) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');
    // Prepare object to be sent to VIP Research's multi-sentence
    // short answer similarity.
    $json = array(
        'key' => $answer,
        'target' => $response,
        'value' => 1,
        'method' => 'old',
        'language' => $language,
        'email' => 'sas@vipresearch.ca'
    );

    $json = json_encode($json);
    $url = 'https://ws-nlp.vipresearch.ca/bridge/';
    $headers = ['Content-Type' => 'application/json'];
    $contents = download_file_content($url, $headers, $json, false, 600);

    return json_decode($contents);
}
