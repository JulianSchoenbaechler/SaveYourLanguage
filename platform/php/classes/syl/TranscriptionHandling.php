<?php
/*
 * Transcirption and user statistic management
 *
 * A class for handling transcriptions from players and its associated statistics.
 *
 * Author           Marcel Arioli
 * Copyright        (c) 2017 University of the Arts, Zurich
 * Included since   v0.0.1
 * Repository       https://github.com/JulianSchoenbaechler/SaveYourLanguage
 */
namespace SaveYourLanguage\Transcriptions;

// Include db controller
require_once dirname(__FILE__).'/../db/DatabaseController.php';
require_once dirname(__FILE__).'/../Config.php';

use SaveYourLanguage\Database\DatabaseController;
use SaveYourLanguage\Config;


class TranscriptionHandling
{
    protected $link;
    protected $dc;

    // Constructor
    public function __construct(&$dbLink = null)
    {
        if ($dbLink !== null)
            $this->link = $dbLink;
        else
            $this->link = DatabaseController::connect();

        $this->dc = new DatabaseController($this->link);
    }

    // Add a new transcription
    public function addTranscription($transcription, $snippetId, $userId)
    {
        // Check arguments
        if (!is_string($transcription)) {
			trigger_error("[TranscriptionHandling] 'addTranscription' expected Argument 0 to be String", E_USER_WARNING);
		}
        if (!is_int($snippetId)) {
			trigger_error("[TranscriptionHandling] 'addTranscription' expected Argument 1 to be Integer", E_USER_WARNING);
		}
        if (!is_int($userId)) {
			trigger_error("[TranscriptionHandling] 'addTranscription' expected Argument 2 to be Integer", E_USER_WARNING);
		}

        $row = $this->dc->getRow('transcriptions', array(
            'snippetId' => $snippetId,
            'userId' => $userId
        ));

        // If row does not already exist
        if ($row === null) {

            $this->dc->insertRow('transcriptions', array(
                'snippetId' => $snippetId,
                'userId' => $userId,
                'transcription' => $transcription,
                'evaluation' => 0,
                'timestamp' => time(),
                'usable' => 1
            ));

        }
    }

    /*
     * Get a new snippet for a user
     * Returns as snippet array or null if no suitable snippet was found:
     * 'id' => snippet id
     * 'path' => path to sound file
     * 'count' => number of transcriptions for this snippet
     * 'done' => if the snippet is done (enough valid transcriptions)
     */
    public function getSnippet($userId)
    {
        if (!is_int($userId)) {
			trigger_error("[TranscriptionHandling] 'getSnippet' expected Argument 0 to be Integer", E_USER_WARNING);
		}

        $query = "SELECT * FROM `snippets` WHERE `done`=? AND `id`
        NOT IN (SELECT `snippetId` FROM `transcriptions` WHERE `userId`=? AND `usable`=?)
        ORDER BY `count` DESC LIMIT 1";

        // Get a pool of snippets which haven't been solved yet
        $snippets = $this->dc->executeCustomQuery($query, array(
            0,          // Not done
            $userId,    // User id
            1           // Usable data
        ));

        if ($snippets !== null)
            return $snippets[0];

        return null;
    }

    // Mark a transcription as unusable
    // This transcription will not be considered for validity and statistic calculations anymore
    public function markAsUnusable($snippetId, $userId)
    {
        // Check arguments
        if (!is_int($snippetId)) {
			trigger_error("[TranscriptionHandling] 'markAsUnusable' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($userId)) {
			trigger_error("[TranscriptionHandling] 'markAsUnusable' expected Argument 1 to be Integer", E_USER_WARNING);
		}

        // Mark as unusable
        $this->dc->updateRow('transcriptions', array(
            'usable' => 0
        ), array(
            'snippetId' => $snippetId,
            'userId' => $userId
        ));
    }

    // Calculate and store validity of transcriptions specified by its id
    public function recalculateValidity($snippetId)
    {
        if (!is_int($snippetId)) {
			trigger_error("[TranscriptionHandling] 'recalculateValidity' expected Argument 0 to be Integer", E_USER_WARNING);
		}

        // Get every usable transcription for this snippet
        $transcriptions = $this->dc->getRows('transcriptions', array(
            'snippetId' => $snippetId,
            'usable' => 1));

        $tCount = count($transcriptions);       // Number of transcriptions
        $maxL = 0;                              // Length of the longest transcription for this snippet
        $lTotal = array();                      // Stores the sums of all Levenshtein comparisations for a transcription
                                                // Added: subtract the amount of equal characters (from comparisation)
        $validity = array();                    // The calculated validity in percent for every transcription
        $averageThreshold = 0;                  // The average of the validity calculations -> serves as threshold
        $validCount = 0;                        // The amount of transcriptions considered 'valid'
        $i = 0;                                 // Counting variable

        // Iterate through all transcriptions
        foreach ($transcriptions as $playerTranscription) {

            // Search for longest transcription
            // -> This is the maximum value the levenshtein formula can possibly show
            if (strlen($playerTranscription['transcription']) > $maxL)
                $maxL = strlen($playerTranscription['transcription']);

            $lTotal[$i] = 0;
            //$sTotal[$i] = 0;

            // Nested iteration -> test every transcription to eachother
            foreach ($transcriptions as $allTranscriptions) {

                // Calculate levenshtein value to every transcription
                // Favour the transcriptions which have characters in common
                // -> Exclude itself;
                if ($allTranscriptions['userId'] != $playerTranscription['userId']) {
                    $lTotal[$i] += levenshtein($playerTranscription['transcription'], $allTranscriptions['transcription']);
                    $lTotal[$i] -= similar_text($playerTranscription['transcription'], $allTranscriptions['transcription']);
                }

            }

            if ($lTotal[$i] < 0)
                $lTotal[$i] = 0;

            // Iterator
            $i++;

        }

        // Calculate and update validity of each players transcription
        for ($i = 0; $i < $tCount; $i++) {

            $validity[$i] = round( 100 - ((100 / $maxL) * ($lTotal[$i] / $tCount)) );
            $averageThreshold += $validity[$i];

            $this->dc->updateRow('transcriptions', array(
                'evaluation' => $validity[$i]
            ), array(
                'userId' => $transcriptions[$i]['userId'],
                'snippetId' => $snippetId
            ));

        }

        // Calculate average validity precentage
        $averageThreshold /= $tCount;

        // How many transcriptions greater than average?
        // Those are going to be considered as valid.
        for ($i = 0; $i < $tCount; $i++) {

            if ($validity[$i] >= $averageThreshold)
                $validCount++;

        }

        // Check if the global threshold of valid transcriptions is reached?
        // Or maximum of transcriptions is reached...
        if (($validCount >= CONFIG::SNIPPET_VALID_COUNT) || ($tCount >= Config::MAX_TRANSCRIPTIONS)) {

            // Snippet is done!
            $this->dc->updateRow('snippets', array(
                'count' => $tCount,
                'done' => 1
            ), array(
                'id' => $snippetId
            ));

        } else {

            $this->dc->updateRow('snippets', array(
                'count' => $tCount
            ), array(
                'id' => $snippetId
            ));

        }
    }
}
