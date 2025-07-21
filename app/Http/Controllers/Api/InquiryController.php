<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class InquiryController extends Controller
{
    /**
     * Listar todos os inquéritos com filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inquiry::with(['vehicle', 'stand', 'assignedTo']);

        // Filtros
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('stand_id')) {
            $query->where('stand_id', $request->stand_id);
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%")
                  ->orWhere('message', 'ilike', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $inquiries = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inquiries->items(),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
            'message' => 'Inquiries retrieved successfully'
        ]);
    }

    /**
     * Criar novo inquérito.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'stand_id' => 'nullable|exists:stands,id',
            'assigned_to' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'type' => 'required|in:general,vehicle,test_drive,financing,trade_in',
            'status' => 'in:new,contacted,qualified,converted,lost',
            'message' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['status'] = $data['status'] ?? 'new';

        $inquiry = Inquiry::create($data);

        return response()->json([
            'success' => true,
            'data' => $inquiry->load(['vehicle', 'stand', 'assignedTo']),
            'message' => 'Inquiry created successfully'
        ], 201);
    }

    /**
     * Visualizar detalhes de um inquérito.
     */
    public function show(string $id): JsonResponse
    {
        $inquiry = Inquiry::with(['vehicle', 'stand', 'assignedTo'])->find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inquiry,
            'message' => 'Inquiry retrieved successfully'
        ]);
    }

    /**
     * Atualizar inquérito.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'stand_id' => 'nullable|exists:stands,id',
            'assigned_to' => 'nullable|exists:users,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'type' => 'sometimes|required|in:general,vehicle,test_drive,financing,trade_in',
            'status' => 'in:new,contacted,qualified,converted,lost',
            'message' => 'sometimes|required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Se o status foi alterado para 'contacted', atualizar contacted_at
        if (isset($data['status']) && $data['status'] === 'contacted' && $inquiry->status !== 'contacted') {
            $data['contacted_at'] = now();
        }

        $inquiry->update($data);

        return response()->json([
            'success' => true,
            'data' => $inquiry->fresh()->load(['vehicle', 'stand', 'assignedTo']),
            'message' => 'Inquiry updated successfully'
        ]);
    }

    /**
     * Remover inquérito.
     */
    public function destroy(string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        $inquiry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inquiry deleted successfully'
        ]);
    }

    /**
     * Atualizar status do inquérito.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,contacted,qualified,converted,lost',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Se o status foi alterado para 'contacted', atualizar contacted_at
        if ($data['status'] === 'contacted' && $inquiry->status !== 'contacted') {
            $data['contacted_at'] = now();
        }

        $inquiry->update($data);

        return response()->json([
            'success' => true,
            'data' => $inquiry->fresh(),
            'message' => 'Inquiry status updated successfully'
        ]);
    }

    /**
     * Atribuir inquérito a um usuário.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $inquiry->update([
            'assigned_to' => $request->assigned_to
        ]);

        return response()->json([
            'success' => true,
            'data' => $inquiry->fresh()->load('assignedTo'),
            'message' => 'Inquiry assigned successfully'
        ]);
    }

    /**
     * Adicionar notas ao inquérito.
     */
    public function addNotes(Request $request, string $id): JsonResponse
    {
        $inquiry = Inquiry::find($id);

        if (!$inquiry) {
            return response()->json([
                'success' => false,
                'message' => 'Inquiry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $currentNotes = $inquiry->notes ?: '';
        $newNotes = $currentNotes . "\n\n" . now()->format('Y-m-d H:i:s') . ":\n" . $request->notes;

        $inquiry->update(['notes' => $newNotes]);

        return response()->json([
            'success' => true,
            'data' => $inquiry->fresh(),
            'message' => 'Notes added successfully'
        ]);
    }

    /**
     * Obter estatísticas de inquéritos.
     */
    public function statistics(): JsonResponse
    {
        $totalInquiries = Inquiry::count();
        $newInquiries = Inquiry::byStatus('new')->count();
        $contactedInquiries = Inquiry::byStatus('contacted')->count();
        $qualifiedInquiries = Inquiry::byStatus('qualified')->count();
        $convertedInquiries = Inquiry::byStatus('converted')->count();
        $lostInquiries = Inquiry::byStatus('lost')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_inquiries' => $totalInquiries,
                'new_inquiries' => $newInquiries,
                'contacted_inquiries' => $contactedInquiries,
                'qualified_inquiries' => $qualifiedInquiries,
                'converted_inquiries' => $convertedInquiries,
                'lost_inquiries' => $lostInquiries,
            ],
            'message' => 'Inquiry statistics retrieved successfully'
        ]);
    }

    /**
     * Obter inquéritos por tipo.
     */
    public function byType(string $type): JsonResponse
    {
        $inquiries = Inquiry::with(['vehicle', 'stand', 'assignedTo'])
            ->byType($type)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $inquiries->items(),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
            'message' => "Inquiries by type {$type} retrieved successfully"
        ]);
    }

    /**
     * Obter inquéritos não atribuídos.
     */
    public function unassigned(): JsonResponse
    {
        $inquiries = Inquiry::with(['vehicle', 'stand'])
            ->whereNull('assigned_to')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $inquiries->items(),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
            'message' => 'Unassigned inquiries retrieved successfully'
        ]);
    }
}
