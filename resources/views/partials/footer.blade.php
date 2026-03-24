<footer class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
	<div class="container mx-auto px-4 py-6 flex items-center justify-between gap-4">
		<p class="text-sm text-gray-500 dark:text-gray-400">
			© {{ now()->year }} Jual Beli Cimanglid
		</p>

		<a href="{{ route('rules.index') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">
			Peraturan Pengguna
		</a>
	</div>
</footer>
