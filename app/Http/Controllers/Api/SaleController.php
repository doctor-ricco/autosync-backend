<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    /**
     * Listar todas as vendas com filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sale::with(['vehicle', 'seller', 'stand']);

        // Filtros
        if ($request->has('seller_id')) {
            $query->bySeller($request->seller_id);
        }

        if ($request->has('stand_id')) {
            $query->where('stand_id', $request->stand_id);
        }

        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('date_from')) {
            $query->whereDate('sold_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('sold_at', '<=', $request->date_to);
        }

        if ($request->has('min_price')) {
            $query->where('sale_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('sale_price', '<=', $request->max_price);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'ilike', "%{$search}%")
                  ->orWhere('customer_email', 'ilike', "%{$search}%")
                  ->orWhere('customer_phone', 'ilike', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'sold_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $sales = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sales->items(),
            'pagination' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
            'message' => 'Sales retrieved successfully'
        ]);
    }

    /**
     * Registrar nova venda.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicles,id',
            'seller_id' => 'required|exists:users,id',
            'stand_id' => 'required|exists:stands,id',
            'sale_price' => 'required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,financing,lease,trade_in',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'sold_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Verificar se o veículo está disponível
        $vehicle = Vehicle::find($data['vehicle_id']);
        if (!$vehicle || $vehicle->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle is not available for sale'
            ], 422);
        }

        // Calcular comissão se não fornecida
        if (!isset($data['commission_amount'])) {
            $seller = User::find($data['seller_id']);
            $commissionRate = $seller->commission_rate ?? 0;
            $data['commission_amount'] = ($data['sale_price'] * $commissionRate) / 100;
        }

        // Criar a venda
        $sale = Sale::create($data);

        // Atualizar status do veículo para vendido
        $vehicle->update(['status' => 'sold']);

        return response()->json([
            'success' => true,
            'data' => $sale->load(['vehicle', 'seller', 'stand']),
            'message' => 'Sale registered successfully'
        ], 201);
    }

    /**
     * Visualizar detalhes de uma venda.
     */
    public function show(string $id): JsonResponse
    {
        $sale = Sale::with(['vehicle', 'seller', 'stand'])->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale,
            'message' => 'Sale retrieved successfully'
        ]);
    }

    /**
     * Atualizar venda.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sale_price' => 'sometimes|required|numeric|min:0',
            'commission_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'sometimes|required|in:cash,financing,lease,trade_in',
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'sold_at' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Recalcular comissão se o preço de venda foi alterado
        if (isset($data['sale_price']) && $data['sale_price'] !== $sale->sale_price) {
            $seller = $sale->seller;
            $commissionRate = $seller->commission_rate ?? 0;
            $data['commission_amount'] = ($data['sale_price'] * $commissionRate) / 100;
        }

        $sale->update($data);

        return response()->json([
            'success' => true,
            'data' => $sale->fresh()->load(['vehicle', 'seller', 'stand']),
            'message' => 'Sale updated successfully'
        ]);
    }

    /**
     * Remover venda.
     */
    public function destroy(string $id): JsonResponse
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        // Reverter status do veículo para disponível
        $sale->vehicle->update(['status' => 'available']);

        $sale->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sale deleted successfully'
        ]);
    }

    /**
     * Obter estatísticas de vendas.
     */
    public function statistics(): JsonResponse
    {
        $totalSales = Sale::count();
        $totalRevenue = Sale::sum('sale_price');
        $totalCommission = Sale::sum('commission_amount');
        $averageSalePrice = Sale::avg('sale_price');

        // Vendas por método de pagamento
        $salesByPaymentMethod = Sale::selectRaw('payment_method, COUNT(*) as count, SUM(sale_price) as total')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'average_sale_price' => $averageSalePrice,
                'sales_by_payment_method' => $salesByPaymentMethod,
            ],
            'message' => 'Sales statistics retrieved successfully'
        ]);
    }

    /**
     * Obter vendas por período.
     */
    public function byPeriod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sales = Sale::with(['vehicle', 'seller', 'stand'])
            ->dateRange($request->start_date, $request->end_date)
            ->orderBy('sold_at', 'desc')
            ->get();

        $totalRevenue = $sales->sum('sale_price');
        $totalCommission = $sales->sum('commission_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => $sales,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'count' => $sales->count(),
            ],
            'message' => 'Sales by period retrieved successfully'
        ]);
    }

    /**
     * Obter vendas por vendedor.
     */
    public function bySeller(string $sellerId): JsonResponse
    {
        $sales = Sale::with(['vehicle', 'stand'])
            ->bySeller($sellerId)
            ->orderBy('sold_at', 'desc')
            ->get();

        $totalRevenue = $sales->sum('sale_price');
        $totalCommission = $sales->sum('commission_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => $sales,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'count' => $sales->count(),
            ],
            'message' => 'Sales by seller retrieved successfully'
        ]);
    }

    /**
     * Obter vendas por stand.
     */
    public function byStand(string $standId): JsonResponse
    {
        $sales = Sale::with(['vehicle', 'seller'])
            ->where('stand_id', $standId)
            ->orderBy('sold_at', 'desc')
            ->get();

        $totalRevenue = $sales->sum('sale_price');
        $totalCommission = $sales->sum('commission_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => $sales,
                'total_revenue' => $totalRevenue,
                'total_commission' => $totalCommission,
                'count' => $sales->count(),
            ],
            'message' => 'Sales by stand retrieved successfully'
        ]);
    }

    /**
     * Obter top vendedores.
     */
    public function topSellers(): JsonResponse
    {
        $topSellers = Sale::selectRaw('seller_id, COUNT(*) as total_sales, SUM(sale_price) as total_revenue, SUM(commission_amount) as total_commission')
            ->with('seller')
            ->groupBy('seller_id')
            ->orderBy('total_sales', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topSellers,
            'message' => 'Top sellers retrieved successfully'
        ]);
    }

    /**
     * Obter relatório de vendas.
     */
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'in:day,week,month,seller,stand',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Sale::dateRange($request->start_date, $request->end_date);

        switch ($request->group_by) {
            case 'day':
                $report = $query->selectRaw('DATE(sold_at) as date, COUNT(*) as sales_count, SUM(sale_price) as revenue, SUM(commission_amount) as commission')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'seller':
                $report = $query->selectRaw('seller_id, COUNT(*) as sales_count, SUM(sale_price) as revenue, SUM(commission_amount) as commission')
                    ->with('seller')
                    ->groupBy('seller_id')
                    ->orderBy('sales_count', 'desc')
                    ->get();
                break;
            case 'stand':
                $report = $query->selectRaw('stand_id, COUNT(*) as sales_count, SUM(sale_price) as revenue, SUM(commission_amount) as commission')
                    ->with('stand')
                    ->groupBy('stand_id')
                    ->orderBy('sales_count', 'desc')
                    ->get();
                break;
            default:
                $report = $query->selectRaw('COUNT(*) as sales_count, SUM(sale_price) as revenue, SUM(commission_amount) as commission')
                    ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Sales report generated successfully'
        ]);
    }
}
