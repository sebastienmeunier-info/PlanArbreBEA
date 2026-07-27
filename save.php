<?php

declare(strict_types=1);

/*
------------------------------------------------------------
PlanArbreBEA
save.php
Version 1.0.0
Licence GNU AGPL v3
------------------------------------------------------------
*/

require_once __DIR__ . '/app/Config.php';
require_once __DIR__ . '/app/Response.php';
require_once __DIR__ . '/app/Validator.php';
require_once __DIR__ . '/app/Territory.php';
require_once __DIR__ . '/app/GeoJSON.php';
require_once __DIR__ . '/app/Photo.php';

Config::init();

try {

    /*
    ------------------------------------------------------------
    Validation du formulaire
    ------------------------------------------------------------
    */

    $data = Validator::proposal($_POST);

    /*
    ------------------------------------------------------------
    Contrôle du territoire
    ------------------------------------------------------------
    */

    if (!Territory::contains(
        $data['latitude'],
        $data['longitude']
    )) {

        Response::forbidden(
            "Le point sélectionné est situé hors du territoire de Baugé-en-Anjou."
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

    $feature = GeoJSON::createProposal(
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

    Response::success(
        [
            'feature' => $feature
        ],
        'Votre proposition a été enregistrée.'
    );

}
catch (InvalidArgumentException $e) {

    Response::error(
        $e->getMessage(),
        400
    );

}
catch (RuntimeException $e) {

    Response::error(
        $e->getMessage(),
        500
    );

}
catch (Throwable $e) {

    Response::serverError(
        $e
    );

}
