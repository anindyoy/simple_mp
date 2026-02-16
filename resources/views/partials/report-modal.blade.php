@php
    $hasErrorForThisForm = $errors->report->any();
@endphp

<div id="report{{ ucfirst($type) }}Modal"
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">

    <div class="bg-white w-full max-w-md p-6 rounded-xl shadow-lg">
        <h3 class="text-lg font-bold mb-4">
            Laporkan {{ $type === 'product' ? 'Produk' : 'Toko' }}
        </h3>

        @if ($errors->report->any())
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
                @foreach ($errors->report->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('report.store') }}">
            @csrf
            <input type="hidden" name="reportable_type" value="{{ $type }}">
            <input type="hidden" name="reportable_id" value="{{ $id }}">

            <div class="mb-3">
                <label class="text-sm font-semibold">Alasan</label>
                <select name="reason" required class="w-full border rounded-lg p-2">
                    <option value="">Pilih alasan</option>
                    <option value="penipuan">Penipuan</option>
                    <option value="produk_terlarang">Produk Terlarang</option>
                    <option value="spam">Spam</option>
                    <option value="konten_tidak_pantas">Konten Tidak Pantas</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="text-sm font-semibold">Keterangan Tambahan</label>
                <textarea name="description" rows="3"
                    class="w-full border rounded-lg p-2"></textarea>
            </div>

            @guest
                <div class="mb-3">
                    <input type="text" name="reporter_name" placeholder="Nama (opsional)"
                        class="w-full border rounded-lg p-2 mb-2">

                    <input type="email" name="reporter_email" placeholder="Email (opsional)"
                        class="w-full border rounded-lg p-2">
                </div>
            @endguest

            <div class="flex justify-end gap-2 mt-4">
                <button type="button"
                    onclick="this.closest('.fixed').classList.add('hidden')"
                    class="px-4 py-2 text-gray-600">
                    Batal
                </button>

                <button type="submit"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                    Kirim Laporan
                </button>
            </div>
        </form>
    </div>
</div>
