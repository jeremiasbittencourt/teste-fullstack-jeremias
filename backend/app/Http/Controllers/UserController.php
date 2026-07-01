<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['name', 'email', 'role', 'is_active', 'created_at', 'last_login_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = (int) $request->input('per_page', 20);

        return response()->json(
            UserResource::collection($query->paginate($perPage))->response()->getData(true)
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => $request->string('password'),
            'role' => UserRole::from($request->input('role')),
            'is_active' => true,
            'is_approved' => true,
        ]);

        return response()->json(new UserResource($user), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $authUser = $request->user();
        $newRole = UserRole::from($request->input('role'));
        $newActive = (bool) $request->input('is_active');

        if ($user->id === $authUser->id && $authUser->role === UserRole::Admin && $newRole !== UserRole::Admin) {
            throw ValidationException::withMessages([
                'role' => ['Você não pode rebaixar o seu próprio perfil de Administrador.'],
            ]);
        }

        if ($user->id === $authUser->id && !$newActive) {
            throw ValidationException::withMessages([
                'is_active' => ['Você não pode inativar a sua própria conta.'],
            ]);
        }

        if (
            $user->role === UserRole::Admin
            && $user->is_active
            && ($newRole !== UserRole::Admin || !$newActive)
            && $this->lastActiveAdmin($user->id)
        ) {
            throw ValidationException::withMessages([
                'role' => ['O sistema deve ter pelo menos 1 administrador ativo.'],
            ]);
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->role = $newRole;
        $user->is_active = $newActive;

        $passwordChanged = false;
        if ($request->filled('password')) {
            $user->password = $request->string('password');
            $passwordChanged = true;
        }

        $user->save();

        if ($passwordChanged || !$newActive) {
            $user->tokens()->delete();
        }

        return response()->json(new UserResource($user));
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if ($user->id === $authUser->id) {
            throw ValidationException::withMessages([
                'is_active' => ['Você não pode alterar o status da sua própria conta.'],
            ]);
        }

        if ($user->is_active && $user->role === UserRole::Admin && $this->lastActiveAdmin($user->id)) {
            throw ValidationException::withMessages([
                'is_active' => ['O sistema deve ter pelo menos 1 administrador ativo.'],
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json(new UserResource($user));
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não corresponde.',
        ]);

        $user->password = $request->string('password');
        $user->save();
        $user->tokens()->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso.']);
    }

    private function lastActiveAdmin(int $userId): bool
    {
        return User::where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->where('id', '!=', $userId)
            ->doesntExist();
    }
}
