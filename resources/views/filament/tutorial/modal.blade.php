<div
    x-data="tutorialModal()"
    x-on:open-tutorial-modal.window="open()"
    x-show="isOpen"
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center"
    style="display: none;">
    {{-- Overlay --}}
    <div
        class="absolute inset-0 bg-black/50"
        @click="close()"></div>

    {{-- Modal Content --}}
    <div class="relative bg-white rounded-xl shadow-xl w-[90%] max-w-3xl p-4 overflow-auto max-h-[80vh]">

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">Tutorial Halaman</h2>

            <button @click="close()" class="text-gray-500 hover:text-black">
                ✕
            </button>
        </div>

        {{-- Content --}}
        <template x-if="loading">
            <div class="text-center py-10">Loading...</div>
        </template>

        <template x-if="!loading && images.length === 0">
            <div class="text-center py-10 text-gray-500">
                Tidak ada tutorial untuk halaman ini
            </div>
        </template>

        <div class="grid grid-cols-1 gap-4">
            <template x-for="img in images" :key="img.url">
                <div>
                    <img :src="img.url" class="rounded-lg border" />

                    <template x-if="img.caption">
                        <p class="text-sm text-gray-500 mt-1" x-text="img.caption"></p>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
    function tutorialModal() {
        return {
            isOpen: false,
            loading: false,
            images: [],

            open() {
                this.isOpen = true
                this.fetchData()
            },

            close() {
                this.isOpen = false
            },

            async fetchData() {
                this.loading = true
                this.images = []

                try {
                    const response = await fetch('/api/tutorial-images?url=' + window.location.pathname)
                    const data = await response.json()

                    this.images = data.images ?? []
                } catch (e) {
                    console.error(e)
                }

                this.loading = false
            }
        }
    }
</script>
