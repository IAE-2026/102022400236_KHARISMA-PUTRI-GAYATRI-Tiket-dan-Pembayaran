<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Layanan Tiket dan Pembayaran Travel",
 *     description="EAI Ticket and Payment Service - 4 strategic endpoints (Listing, Detail, Payment, E-Ticket Issuance)",
 *     @OA\Contact(email="102022400236@student.ac.id")
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Docker / Local Development"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="IAEKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-IAE-KEY",
 *     description="Kunci integrasi EAI - masukkan NIM: 102022400236 lalu klik Authorize"
 * )
 *
 * @OA\Tag(name="Tickets", description="Siklus hidup tiket: listing, detail, pembayaran, e-ticket")
 *
 * @OA\Schema(
 *     schema="ApiSuccess",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ApiError",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="data", nullable=true, example=null)
 * )
 *
 * @OA\Schema(
 *     schema="TicketData",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="cust_name", type="string", example="Budi Santoso"),
 *     @OA\Property(property="route_id", type="integer", example=10),
 *     @OA\Property(property="seat_number", type="string", example="A12"),
 *     @OA\Property(property="status", type="string", enum={"booked","cancelled"}),
 *     @OA\Property(property="price", type="number", format="float", example=150000),
 *     @OA\Property(property="payment", ref="#/components/schemas/PaymentData")
 * )
 *
 * @OA\Schema(
 *     schema="PaymentData",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="ticket_id", type="integer", example=1),
 *     @OA\Property(property="amount", type="number", format="float", example=150000),
 *     @OA\Property(property="payment_method", type="string", enum={"credit_card","bank_transfer","QRIS"}),
 *     @OA\Property(property="status", type="string", enum={"pending","completed","failed"}),
 *     @OA\Property(property="payment_date", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="ETicketData",
 *     type="object",
 *     @OA\Property(property="ticket_id", type="integer", example=1),
 *     @OA\Property(property="e_ticket_code", type="string", example="ETK-000001-ABC123"),
 *     @OA\Property(property="cust_name", type="string", example="Budi Santoso"),
 *     @OA\Property(property="route_id", type="integer", example=10),
 *     @OA\Property(property="seat_number", type="string", example="A12"),
 *     @OA\Property(property="price", type="number", format="float", example=150000),
 *     @OA\Property(property="payment_status", type="string", example="completed"),
 *     @OA\Property(property="issued_at", type="string", format="date-time")
 * )
 *
 * @OA\Get(
 *     path="/api/v1/tickets",
 *     operationId="listTickets",
 *     tags={"Tickets"},
 *     summary="Daftar / riwayat pesanan tiket",
 *     security={{"IAEKey":{}}},
 *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"booked","cancelled"})),
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
 *     @OA\Response(
 *         response=200,
 *         description="Daftar tiket",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccess"),
 *                 @OA\Schema(@OA\Property(property="data", type="object"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiError"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/tickets/{id}",
 *     operationId="showTicket",
 *     tags={"Tickets"},
 *     summary="Detail spesifik satu tiket",
 *     security={{"IAEKey":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="Detail tiket",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccess"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/TicketData"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ApiError")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiError"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/tickets/{id}/payments",
 *     operationId="processTicketPayment",
 *     tags={"Tickets"},
 *     summary="Proses pembayaran tiket berdasarkan ID",
 *     security={{"IAEKey":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"payment_method"},
 *             @OA\Property(property="payment_method", type="string", enum={"credit_card","bank_transfer","QRIS"}, example="QRIS")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Pembayaran berhasil",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccess"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/PaymentData"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiError")),
 *     @OA\Response(response=404, description="Ticket not found", @OA\JsonContent(ref="#/components/schemas/ApiError")),
 *     @OA\Response(response=409, description="Payment already completed", @OA\JsonContent(ref="#/components/schemas/ApiError"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/tickets/{id}/send",
 *     operationId="sendETicket",
 *     tags={"Tickets"},
 *     summary="Kirim / tampilkan E-Ticket yang sudah lunas",
 *     security={{"IAEKey":{}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 *     @OA\Response(
 *         response=200,
 *         description="E-Ticket berhasil dikeluarkan",
 *         @OA\JsonContent(
 *             allOf={
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccess"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/ETicketData"))
 *             }
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ApiError")),
 *     @OA\Response(response=402, description="Payment not completed", @OA\JsonContent(ref="#/components/schemas/ApiError")),
 *     @OA\Response(response=404, description="Ticket not found", @OA\JsonContent(ref="#/components/schemas/ApiError"))
 * )
 */
class SwaggerInfo {}
