<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuditLogController extends Controller
{
    /**
     * Listar logs de auditoria com filtros.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with(['user']);

        // Filtros
        if ($request->has('action')) {
            $query->byAction($request->action);
        }

        if ($request->has('table_name')) {
            $query->byTable($request->table_name);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('record_id')) {
            $query->where('record_id', $request->record_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'ilike', "%{$search}%")
                  ->orWhere('table_name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => 'Audit logs retrieved successfully'
        ]);
    }

    /**
     * Visualizar detalhes de um log de auditoria.
     */
    public function show(string $id): JsonResponse
    {
        $log = AuditLog::with(['user'])->find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Audit log not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
            'message' => 'Audit log retrieved successfully'
        ]);
    }

    /**
     * Obter estatísticas de auditoria.
     */
    public function statistics(): JsonResponse
    {
        $totalLogs = AuditLog::count();
        $todayLogs = AuditLog::today()->count();
        $uniqueUsers = AuditLog::whereNotNull('user_id')->distinct('user_id')->count();
        $uniqueActions = AuditLog::distinct('action')->count();

        // Logs por ação
        $logsByAction = AuditLog::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();

        // Logs por tabela
        $logsByTable = AuditLog::selectRaw('table_name, COUNT(*) as count')
            ->whereNotNull('table_name')
            ->groupBy('table_name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_logs' => $totalLogs,
                'today_logs' => $todayLogs,
                'unique_users' => $uniqueUsers,
                'unique_actions' => $uniqueActions,
                'logs_by_action' => $logsByAction,
                'logs_by_table' => $logsByTable,
            ],
            'message' => 'Audit statistics retrieved successfully'
        ]);
    }

    /**
     * Obter logs por ação.
     */
    public function byAction(string $action): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->byAction($action)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => "Audit logs by action '{$action}' retrieved successfully"
        ]);
    }

    /**
     * Obter logs por tabela.
     */
    public function byTable(string $tableName): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->byTable($tableName)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => "Audit logs by table '{$tableName}' retrieved successfully"
        ]);
    }

    /**
     * Obter logs por usuário.
     */
    public function byUser(string $userId): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => 'Audit logs by user retrieved successfully'
        ]);
    }

    /**
     * Obter logs por período.
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

        $logs = AuditLog::with(['user'])
            ->whereBetween('created_at', [
                $request->start_date,
                $request->end_date . ' 23:59:59'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'message' => 'Audit logs by period retrieved successfully'
        ]);
    }

    /**
     * Obter relatório de auditoria.
     */
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'in:day,action,table,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = AuditLog::whereBetween('created_at', [
            $request->start_date,
            $request->end_date . ' 23:59:59'
        ]);

        switch ($request->group_by) {
            case 'day':
                $report = $query->selectRaw('DATE(created_at) as date, COUNT(*) as logs_count, COUNT(DISTINCT user_id) as unique_users')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
                break;
            case 'action':
                $report = $query->selectRaw('action, COUNT(*) as logs_count')
                    ->groupBy('action')
                    ->orderBy('logs_count', 'desc')
                    ->get();
                break;
            case 'table':
                $report = $query->selectRaw('table_name, COUNT(*) as logs_count')
                    ->whereNotNull('table_name')
                    ->groupBy('table_name')
                    ->orderBy('logs_count', 'desc')
                    ->get();
                break;
            case 'user':
                $report = $query->selectRaw('user_id, COUNT(*) as logs_count')
                    ->whereNotNull('user_id')
                    ->with('user')
                    ->groupBy('user_id')
                    ->orderBy('logs_count', 'desc')
                    ->get();
                break;
            default:
                $report = $query->selectRaw('COUNT(*) as total_logs, COUNT(DISTINCT user_id) as unique_users, COUNT(DISTINCT action) as unique_actions')
                    ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $report,
            'message' => 'Audit report generated successfully'
        ]);
    }

    /**
     * Obter logs de login.
     */
    public function loginLogs(): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->byAction('login')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => 'Login audit logs retrieved successfully'
        ]);
    }

    /**
     * Obter logs de criação.
     */
    public function createLogs(): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->byAction('create')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => 'Create audit logs retrieved successfully'
        ]);
    }

    /**
     * Obter logs de atualização.
     */
    public function updateLogs(): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->byAction('update')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => 'Update audit logs retrieved successfully'
        ]);
    }

    /**
     * Obter logs de exclusão.
     */
    public function deleteLogs(): JsonResponse
    {
        $logs = AuditLog::with(['user'])
            ->byAction('delete')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => 'Delete audit logs retrieved successfully'
        ]);
    }

    /**
     * Obter logs de um registro específico.
     */
    public function byRecord(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'table_name' => 'required|string',
            'record_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $logs = AuditLog::with(['user'])
            ->where('table_name', $request->table_name)
            ->where('record_id', $request->record_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'message' => 'Record audit logs retrieved successfully'
        ]);
    }
}
