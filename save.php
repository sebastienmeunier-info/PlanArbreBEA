<?php

declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * PlanArbreBEA
 * Enregistrement d'une proposition
 * Version : 1.0.0
 * Licence : GNU AGPL v3
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/app/autoload.php';

Config::init();

try {

    /*
    ------------------------------------------------------------
    Vérification de la méthode HTTP
    ------------------------------------------------------------
    */

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        Response::error(
            'Méthode HTTP non autorisée.',
            405
        );

    }

    /*
    ------------------------------------------------------------
    Validation des données
    ------------------------------------------------------------
    */

    $data = Validator::proposal($_POST);

    /*
    ------------------------------------------------------------
    Vérification du territoire
    ------------------------------------------------------------
    */

    if (
        !Territory::contains(
            $data['latitude'],
            $data['longitude']
        )
    ) {

        Response::forbidden(
            'Le point sélectionné est situé hors du territoire de Baugé-en-Anjou.'
        );

    }

    /*
    ------------------------------------------------------------
    Upload de la photo
    ------------------------------------------------------------
    */

    $photo = Photo::upload(
        $_FILES['photo'] ?? null
    );

    /*
    ------------------------------------------------------------
    Création de la Feature GeoJSON
    ------------------------------------------------------------
    */

    $feature = Feature::proposal(
        $data,
        $photo
    );

    /*
    ------------------------------------------------------------
    Sauvegarde
    ------------------------------------------------------------
    */

    GeoJSON::appendProposal(
        $feature
    );

    /*
    ------------------------------------------------------------
    Réponse
    ------------------------------------------------------------
    */

    Response::created(

        [
            'feature' => $feature
        ],

        'Votre proposition a été enregistrée avec succès.'

    );

}

/*
------------------------------------------------------------
Erreurs de validation
------------------------------------------------------------
*/

catch (InvalidArgumentException $e) {

    Response::badRequest(
        $e->getMessage()
    );

}

/*
------------------------------------------------------------
Erreurs applicatives
------------------------------------------------------------
*/

catch (RuntimeException $e) {

    Response::error(
        $e->getMessage(),
        500
    );

}

/*
------------------------------------------------------------
Erreur inattendue
------------------------------------------------------------
*/

catch (Throwable $e) {

    Response::serverError(
        $e
    );

}
