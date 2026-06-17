@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="-mt-2 relative z-10" aria-label="{{ __('Pagination Navigation') }}" class="-mt-2 relative z-10" aria-label="{{ __('Pagination Navigation') }}" class="-mt-2 relative z-10">

        {{-- Mobile View --}}
        <div class="flex gap-2 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-400 bg-white/30 border border-white/40 backdrop-blur-md cursor-not-allowed leading-5 rounded-xl dark:text-gray-500 dark:bg-slate-800/30 dark:border-white/5">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-700 bg-white/60 border border-white/60 backdrop-blur-md leading-5 rounded-xl hover:bg-white/80 hover:text-blue-600 transition ease-in-out duration-200 dark:bg-slate-800/60 dark:border-white/10 dark:text-gray-200 dark:hover:bg-slate-700/80 dark:hover:text-blue-400">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-700 bg-white/60 border border-white/60 backdrop-blur-md leading-5 rounded-xl hover:bg-white/80 hover:text-blue-600 transition ease-in-out duration-200 dark:bg-slate-800/60 dark:border-white/10 dark:text-gray-200 dark:hover:bg-slate-700/80 dark:hover:text-blue-400">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-400 bg-white/30 border border-white/40 backdrop-blur-md cursor-not-allowed leading-5 rounded-xl dark:text-gray-500 dark:bg-slate-800/30 dark:border-white/5">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        {{-- Desktop View --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between bg-white/40 dark:bg-slate-800/40 backdrop-blur-xl border border-white/50 dark:border-white/10 p-2 px-4 rounded-2xl shadow-lg">

            {{-- Text Info --}}
            <div>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-bold text-slate-800 dark:text-white">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            {{-- Links --}}
            <div>
                <span class="inline-flex items-center gap-1">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="inline-flex items-center justify-center w-9 h-9 text-gray-400 bg-transparent cursor-not-allowed rounded-lg dark:text-gray-600" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-white/50 border border-white/60 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-md transition-all duration-200 dark:text-slate-300 dark:bg-slate-700/50 dark:border-white/10 dark:hover:bg-slate-700 dark:hover:text-blue-400" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center justify-center w-9 h-9 text-sm font-bold text-slate-400 dark:text-slate-500 cursor-default">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm font-black text-white bg-gradient-to-br from-blue-600 to-indigo-600 rounded-lg shadow-lg shadow-blue-500/30 dark:shadow-blue-500/20 cursor-default transform scale-110 mx-1">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center justify-center w-9 h-9 text-sm font-bold text-slate-600 bg-white/50 border border-white/60 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-md transition-all duration-200 dark:text-slate-300 dark:bg-slate-700/50 dark:border-white/10 dark:hover:bg-slate-700 dark:hover:text-blue-400" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-white/50 border border-white/60 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-md transition-all duration-200 dark:text-slate-300 dark:bg-slate-700/50 dark:border-white/10 dark:hover:bg-slate-700 dark:hover:text-blue-400" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="inline-flex items-center justify-center w-9 h-9 text-gray-400 bg-transparent cursor-not-allowed rounded-lg dark:text-gray-600" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
