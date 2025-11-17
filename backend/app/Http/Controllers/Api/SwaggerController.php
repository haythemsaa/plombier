<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     version="1.2.0",
 *     title="ServiceHub Tunisie API",
 *     description="API complète pour la plateforme ServiceHub - Marketplace de services à domicile en Tunisie",
 *     @OA\Contact(
 *         email="contact@servicehub.tn"
 *     ),
 *     @OA\License(
 *         name="Propriétaire",
 *         url="https://servicehub.tn/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints pour l'authentification des utilisateurs"
 * )
 *
 * @OA\Tag(
 *     name="Services",
 *     description="Gestion des services disponibles"
 * )
 *
 * @OA\Tag(
 *     name="Providers",
 *     description="Gestion des prestataires de services"
 * )
 *
 * @OA\Tag(
 *     name="Bookings",
 *     description="Gestion des réservations"
 * )
 *
 * @OA\Tag(
 *     name="Reviews",
 *     description="Système d'avis et notations"
 * )
 *
 * @OA\Tag(
 *     name="Addresses",
 *     description="Gestion des adresses"
 * )
 *
 * @OA\Tag(
 *     name="Payments",
 *     description="Gestion des paiements"
 * )
 *
 * @OA\Tag(
 *     name="Notifications",
 *     description="Système de notifications"
 * )
 */
class SwaggerController extends Controller
{
    //
}
