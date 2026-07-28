<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\People\Models\AksesLog;
use Modules\People\Models\Batch;
use Modules\People\Models\Ketidakhadiran;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

#[Layout('components.layouts.app')]
class KelolaTim extends Component
{
    public string $pencarian = '';

    public string $filterStatus = 'aktif';

    public string $filterRole = '';

    public ?int $anggotaId = null;

    public bool $formAnggotaTerbuka = false;

    public string $nama = '';

    public string $email = '';

    public string $password = '';

    public int|string $roleId = '';

    public array $izinTambahan = [];

    public string $aktifMulai = '';

    public string $aktifSampai = '';

    public int|string $batchId = '';

    public string $statusAnggota = 'aktif';

    public string $alasanPerpanjangan = '';

    public bool $formBatchTerbuka = false;

    public string $namaBatch = '';

    public string $batchMulai = '';

    public string $batchSelesai = '';

    public ?int $ketidakhadiranUserId = null;

    public bool $formKetidakhadiranTerbuka = false;

    public string $jenisKetidakhadiran = 'izin';

    public string $ketidakhadiranMulai = '';

    public string $ketidakhadiranSelesai = '';

    public string $catatanKetidakhadiran = '';

    public function mount(): void
    {
        Gate::authorize('kelola_pengguna');
    }

    public function buatAnggota(): void
    {
        Gate::authorize('kelola_pengguna');
        $this->resetFormAnggota();
        $this->formAnggotaTerbuka = true;
    }

    public function editAnggota(int $anggotaId): void
    {
        Gate::authorize('kelola_pengguna');
        $anggota = User::withTrashed()->with('izinTambahan')->findOrFail($anggotaId);

        $this->resetFormAnggota();
        $this->anggotaId = $anggota->id;
        $this->nama = $anggota->nama;
        $this->email = $anggota->email;
        $this->roleId = $anggota->role_id ?? '';
        $this->izinTambahan = $anggota->izinTambahan->pluck('id')->all();
        $this->aktifMulai = $anggota->aktif_mulai?->format('Y-m-d') ?? '';
        $this->aktifSampai = $anggota->aktif_sampai?->format('Y-m-d') ?? '';
        $this->batchId = $anggota->batch_id ?? '';
        $this->statusAnggota = $anggota->status;
        $this->formAnggotaTerbuka = true;
    }

    public function simpanAnggota(): void
    {
        Gate::authorize('kelola_pengguna');
        $role = Role::find($this->roleId);
        $magang = $role?->slug === 'magang';

        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->anggotaId)],
            'password' => [$this->anggotaId ? 'nullable' : 'required', 'string', 'min:8'],
            'roleId' => ['required', 'integer', Rule::exists('roles', 'id')],
            'izinTambahan' => ['array'],
            'izinTambahan.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
            'aktifMulai' => [$magang ? 'required' : 'nullable', 'date'],
            'aktifSampai' => [$magang ? 'required' : 'nullable', 'date', 'after_or_equal:aktifMulai'],
            'batchId' => [$magang ? 'required' : 'nullable', 'integer', Rule::exists('batches', 'id')],
            'statusAnggota' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'alasanPerpanjangan' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'roleId' => 'peran',
            'izinTambahan' => 'izin tambahan',
            'aktifMulai' => 'awal masa akses',
            'aktifSampai' => 'akhir masa akses',
            'batchId' => 'batch magang',
            'statusAnggota' => 'status anggota',
        ]);

        DB::transaction(function () use ($data) {
            $anggota = $this->anggotaId ? User::withTrashed()->findOrFail($this->anggotaId) : new User;
            $akhirLama = $anggota->exists ? $anggota->aktif_sampai?->format('Y-m-d') : null;
            $akhirBaru = $data['aktifSampai'] ?: null;

            $anggota->fill([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'role_id' => $data['roleId'],
                'aktif_mulai' => $data['aktifMulai'] ?: null,
                'aktif_sampai' => $akhirBaru,
                'batch_id' => $data['batchId'] ?: null,
                'status' => $data['statusAnggota'],
            ]);

            if ($data['password']) {
                $anggota->password = $data['password'];
            }

            $anggota->save();
            $anggota->izinTambahan()->sync(array_map('intval', $data['izinTambahan']));

            if ($anggota->wasRecentlyCreated === false && $akhirBaru && $akhirLama !== $akhirBaru) {
                AksesLog::create([
                    'user_id' => $anggota->id,
                    'aktif_sampai_lama' => $akhirLama,
                    'aktif_sampai_baru' => $akhirBaru,
                    'oleh_id' => Auth::id(),
                    'alasan' => $data['alasanPerpanjangan'] ?: null,
                ]);
            }
        });

        $this->resetFormAnggota();
        session()->flash('tim-tersimpan', 'Data anggota berhasil disimpan.');
    }

    public function buatBatch(): void
    {
        Gate::authorize('kelola_pengguna');
        $this->resetFormBatch();
        $this->batchMulai = now()->startOfMonth()->format('Y-m-d');
        $this->batchSelesai = now()->addMonths(3)->endOfMonth()->format('Y-m-d');
        $this->formBatchTerbuka = true;
    }

    public function simpanBatch(): void
    {
        Gate::authorize('kelola_pengguna');
        $data = $this->validate([
            'namaBatch' => ['required', 'string', 'max:255'],
            'batchMulai' => ['required', 'date'],
            'batchSelesai' => ['required', 'date', 'after_or_equal:batchMulai'],
        ], [], [
            'namaBatch' => 'nama batch',
            'batchMulai' => 'awal batch',
            'batchSelesai' => 'akhir batch',
        ]);

        Batch::create([
            'nama' => $data['namaBatch'],
            'mulai' => $data['batchMulai'],
            'selesai' => $data['batchSelesai'],
        ]);

        $this->resetFormBatch();
        session()->flash('tim-tersimpan', 'Batch magang berhasil ditambahkan.');
    }

    public function catatKetidakhadiran(int $userId): void
    {
        Gate::authorize('kelola_pengguna');
        User::findOrFail($userId);
        $this->resetFormKetidakhadiran();
        $this->ketidakhadiranUserId = $userId;
        $this->ketidakhadiranMulai = today()->format('Y-m-d');
        $this->ketidakhadiranSelesai = today()->format('Y-m-d');
        $this->formKetidakhadiranTerbuka = true;
    }

    public function simpanKetidakhadiran(): void
    {
        Gate::authorize('kelola_pengguna');
        $data = $this->validate([
            'ketidakhadiranUserId' => ['required', 'integer', Rule::exists('users', 'id')],
            'jenisKetidakhadiran' => ['required', Rule::in(['cuti', 'izin', 'sakit'])],
            'ketidakhadiranMulai' => ['required', 'date'],
            'ketidakhadiranSelesai' => ['required', 'date', 'after_or_equal:ketidakhadiranMulai'],
            'catatanKetidakhadiran' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'jenisKetidakhadiran' => 'jenis ketidakhadiran',
            'ketidakhadiranMulai' => 'tanggal mulai',
            'ketidakhadiranSelesai' => 'tanggal selesai',
            'catatanKetidakhadiran' => 'catatan',
        ]);

        Ketidakhadiran::create([
            'user_id' => $data['ketidakhadiranUserId'],
            'jenis' => $data['jenisKetidakhadiran'],
            'mulai' => $data['ketidakhadiranMulai'],
            'selesai' => $data['ketidakhadiranSelesai'],
            'catatan' => $data['catatanKetidakhadiran'] ?: null,
            'dicatat_oleh' => Auth::id(),
        ]);

        $this->resetFormKetidakhadiran();
        session()->flash('tim-tersimpan', 'Ketidakhadiran berhasil dicatat.');
    }

    public function tutupForm(): void
    {
        $this->resetFormAnggota();
        $this->resetFormBatch();
        $this->resetFormKetidakhadiran();
    }

    public function render()
    {
        $anggota = User::with(['role', 'batch', 'izinTambahan'])
            ->when($this->pencarian !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('nama', 'like', '%'.$this->pencarian.'%')
                ->orWhere('email', 'like', '%'.$this->pencarian.'%')))
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterRole !== '', fn ($query) => $query->where('role_id', $this->filterRole))
            ->orderBy('nama')
            ->get();
        $ketidakhadiran = Ketidakhadiran::whereIn('user_id', $anggota->pluck('id'))
            ->whereDate('selesai', '>=', today())
            ->orderBy('mulai')
            ->get()
            ->groupBy('user_id');

        return view('livewire.kelola-tim', [
            'anggota' => $anggota,
            'roles' => Role::orderBy('nama')->get(),
            'permissions' => Permission::orderBy('nama')->get(),
            'batches' => Batch::latest('mulai')->get(),
            'ketidakhadiran' => $ketidakhadiran,
            'totalAktif' => User::where('status', 'aktif')->count(),
            'aksesAkanHabis' => User::where('status', 'aktif')
                ->whereBetween('aktif_sampai', [today(), today()->addDays(30)])
                ->count(),
        ]);
    }

    private function resetFormAnggota(): void
    {
        $this->reset([
            'anggotaId', 'formAnggotaTerbuka', 'nama', 'email', 'password',
            'roleId', 'izinTambahan', 'aktifMulai', 'aktifSampai', 'batchId',
            'alasanPerpanjangan',
        ]);
        $this->statusAnggota = 'aktif';
        $this->resetValidation();
    }

    private function resetFormBatch(): void
    {
        $this->reset(['formBatchTerbuka', 'namaBatch', 'batchMulai', 'batchSelesai']);
        $this->resetValidation();
    }

    private function resetFormKetidakhadiran(): void
    {
        $this->reset([
            'ketidakhadiranUserId', 'formKetidakhadiranTerbuka',
            'ketidakhadiranMulai', 'ketidakhadiranSelesai', 'catatanKetidakhadiran',
        ]);
        $this->jenisKetidakhadiran = 'izin';
        $this->resetValidation();
    }
}
