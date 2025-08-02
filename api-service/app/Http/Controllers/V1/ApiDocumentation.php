<?php

namespace App\Http\Controllers\V1;

/**
 * Main API Documentation
 * 
 * @OA\Info(
 *     title="Bitexroom API",
 *     version="1.0.0",
 *     description="Main API documentation for Bitexroom exchange platform",
 *     @OA\Contact(
 *         email="o.shabani@hotmail.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token in format: Bearer {token}"
 * )
 */
class ApiDocumentation
{
    // This class is only for Swagger documentation purposes
}