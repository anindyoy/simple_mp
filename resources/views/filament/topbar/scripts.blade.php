{{-- <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('countdownPush', (targetTime) => ({
            label: '',
            timer: null,

            start() {
                this.tick()
                this.timer = setInterval(() => this.tick(), 1000)
            },

            tick() {
                const now = Date.now()
                const diff = targetTime - now

                if (diff <= 0) {
                    this.label = 'Push dimulai'
                    clearInterval(this.timer)
                    return
                }

                const h = Math.floor(diff / 1000 / 60 / 60)
                const m = Math.floor((diff / 1000 / 60) % 60)
                const s = Math.floor((diff / 1000) % 60)

                this.label = `Push dalam ${h}j ${m}m ${s}d`
            },
        }))
    })
</script> --}}


<script>
    console.log('🔥 countdown script loaded')

    document.addEventListener('alpine:init', () => {
        console.log('🔥 alpine:init fired')

        Alpine.data('countdownPush', (targetTime) => ({
            label: 'init...',
            timer: null,

            start() {
                this.tick()
                this.timer = setInterval(() => this.tick(), 1000)
            },

            tick() {
                const diff = targetTime - Date.now()

                if (diff <= 0) {
                    this.label = 'Push dimulai'
                    clearInterval(this.timer)
                    return
                }

                const h = Math.floor(diff / 1000 / 60 / 60)
                const m = Math.floor((diff / 1000 / 60) % 60)
                const s = Math.floor((diff / 1000) % 60)

                this.label = `Push dalam ${h}j ${m}m ${s}d`
            },
        }))
    })
</script>
