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

use SaveYourLanguage\Database\DatabaseController;


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
    public function addTranscription($transcription, $snippedId, $userId)
    {
        // Check arguments
        if (!is_string($transcription)) {
			trigger_error("[TranscriptionHandling] 'addTranscription' expected Argument 0 to be String", E_USER_WARNING);
		}
        if (!is_int($snippedId)) {
			trigger_error("[TranscriptionHandling] 'addTranscription' expected Argument 1 to be Integer", E_USER_WARNING);
		}
        if (!is_int($userId)) {
			trigger_error("[TranscriptionHandling] 'addTranscription' expected Argument 2 to be Integer", E_USER_WARNING);
		}

        $row = $this->dc->getRow('transcriptions', array(
            'snippedId' => $snippedId,
            'userId' => $userId
        ));

        // If row does not already exist
        if ($row === null) {

            $this->dc->insertRow('transcriptions', array(
                'snippedId' => $snippedId,
                'userId' => $userId,
                'transcription' => $transcription,
                'evaluation' => 0,
                'timestamp' => time(),
                'usable' => 1
            ));

        }
    }

    // Mark a transcription as unusable
    // This transcription will not be considered for validity and statistic calculations anymore
    public function markAsUnusable($snippedId, $userId)
    {
        // Check arguments
        if (!is_int($snippedId)) {
			trigger_error("[TranscriptionHandling] 'markAsUnusable' expected Argument 0 to be Integer", E_USER_WARNING);
		}
        if (!is_int($userId)) {
			trigger_error("[TranscriptionHandling] 'markAsUnusable' expected Argument 1 to be Integer", E_USER_WARNING);
		}

        // Mark as unusable
        $this->dc->updateRow('transcriptions', array(
            'usable' => 0
        ), array(
            'snippedId' => $snippedId,
            'userId' => $userId
        ));
    }

    // Calculate and store validity of transcriptions specified by its id
    public function recalculateValidity($snippedId)
    {
        if (!is_int($snippedId)) {
			trigger_error("[TranscriptionHandling] 'recalculateValidity' expected Argument 0 to be Integer", E_USER_WARNING);
		}

        // Get every usable transcription for this snipped
        $transcriptions = $this->dc->getRows('transcriptions', array(
            'snippedId' => $snippedId,
            'usable' => 1));

        $tCount = count($transcriptions);
        $maxL = 0;
        $lTotal = array();
        $validity = array();
        $averageThreshold = 0;
        $validCount = 0;
        $i = 0;

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
                'snippedId' => $snippedId
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
        // TODO: replace with config variable
        if ($validCount >= 5) {

            // Snippet is done!
            $this->dc->updateRow('snippets', array(
                'done' => 1
            ), array(
                'id' => $snippedId
            ));

        }
    }
}
