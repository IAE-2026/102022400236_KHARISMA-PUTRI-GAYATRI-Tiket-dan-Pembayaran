<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TicketResource;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\ETicketService;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ETicketService $eTicketService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $query = Ticket::query()->with('payment')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $tickets = $query->paginate($perPage);

        return ApiResponse::success(
            TicketResource::collection($tickets)->response()->getData(true),
            'Ticket list retrieved successfully'
        );
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::query()->create([
            'Cust_Name'   => $request->string('cust_name')->toString(),
            'route_id'    => $request->integer('route_id'),
            'seat_number' => $request->string('seat_number')->toString(),
            'status'      => 'booked',
            'price'       => $request->input('price'),
        ]);

        Payment::query()->create([
            'ticket_id'      => $ticket->id,
            'amount'         => $request->input('price'),
            'payment_method' => $request->input('payment_method'),
            'status'         => 'pending',
        ]);

        $ticket->load('payment');

        return ApiResponse::success(
            new TicketResource($ticket),
            'Ticket created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $ticket = Ticket::query()->with('payment')->find($id);

        if (! $ticket) {
            return ApiResponse::error('Ticket not found', null, 404);
        }

        return ApiResponse::success(
            new TicketResource($ticket),
            'Ticket detail retrieved successfully'
        );
    }

    public function pay(ProcessPaymentRequest $request, int $id): JsonResponse
    {
        $ticket = Ticket::query()->find($id);

        if (! $ticket) {
            return ApiResponse::error('Ticket not found', null, 404);
        }

        $payment = $this->paymentService->process(
            $ticket,
            $request->string('payment_method')->toString()
        );

        return ApiResponse::success(
            new PaymentResource($payment),
            'Payment processed successfully'
        );
    }

    public function send(int $id): JsonResponse
    {
        $ticket = Ticket::query()->with('payment')->find($id);

        if (! $ticket) {
            return ApiResponse::error('Ticket not found', null, 404);
        }

        $eTicket = $this->eTicketService->issue($ticket);

        return ApiResponse::success(
            $eTicket,
            'E-Ticket issued successfully'
        );
    }
}
