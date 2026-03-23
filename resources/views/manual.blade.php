@extends('languages::component.table-section')
@section('content')
    <div class="py-6 sm:py-8">
        <header class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-blue-50 p-6 shadow-sm dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-slate-800">
            <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-blue-200/50 blur-2xl dark:bg-sky-600/20"></div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 dark:text-sky-300">Documentation</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('languages::navbar.manual') }}</h1>
                <p class="mt-3 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
                    Operational guide for package users. Use the quick links to jump directly to the sections you need.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="#languages" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700 transition hover:bg-blue-100 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20">Languages</a>
                    <a href="#translators" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700 transition hover:bg-blue-100 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20">Translators</a>
                    <a href="#settings" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-700 transition hover:bg-blue-100 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:bg-sky-500/20">Settings</a>
                </div>
            </div>
        </header>

        <div class="mt-6 grid gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:sticky xl:top-4">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Sections</h2>
                    @if(count($manualSections))
                        <ul class="mt-3 space-y-1">
                            @foreach($manualSections as $section)
                                <li>
                                    <a
                                        href="#{{ $section['id'] }}"
                                        class="block rounded-md px-2 py-1.5 text-sm transition {{ $section['level'] === 3 ? 'ml-3 text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100' : 'font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white' }}"
                                    >
                                        {{ $section['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No sections available.</p>
                    @endif
                </div>
            </aside>

            <article class="xl:col-span-9 rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:p-6">
                <div class="prose prose-slate max-w-none
                            prose-headings:font-bold prose-headings:tracking-tight
                            prose-h2:mt-10 prose-h2:border-t prose-h2:border-slate-200 prose-h2:pt-6 prose-h2:text-2xl prose-h2:scroll-mt-24
                            prose-h3:mt-6 prose-h3:text-xl prose-h3:scroll-mt-24
                            prose-p:text-slate-700 prose-p:leading-7
                            prose-li:text-slate-700 prose-li:leading-7
                            prose-strong:text-slate-900
                            prose-a:font-semibold prose-a:text-blue-700 hover:prose-a:text-blue-800
                            prose-code:rounded prose-code:bg-slate-100 prose-code:px-1.5 prose-code:py-0.5 prose-code:text-slate-900
                            prose-pre:rounded-xl prose-pre:border prose-pre:border-slate-200 prose-pre:bg-slate-100
                            prose-blockquote:rounded-lg prose-blockquote:border-l-4 prose-blockquote:border-blue-300 prose-blockquote:bg-blue-50 prose-blockquote:px-4 prose-blockquote:py-2 prose-blockquote:text-blue-900
                            dark:prose-invert dark:prose-headings:text-slate-100 dark:prose-strong:text-slate-100
                            dark:prose-p:text-slate-300 dark:prose-li:text-slate-300
                            dark:prose-a:text-sky-300 dark:hover:prose-a:text-sky-200
                            dark:prose-code:bg-slate-800 dark:prose-code:text-slate-100
                            dark:prose-pre:border-slate-700 dark:prose-pre:bg-slate-950
                            dark:prose-blockquote:border-sky-500 dark:prose-blockquote:bg-sky-500/10 dark:prose-blockquote:text-sky-200
                            dark:prose-hr:border-slate-700">
                    {!! $manualHtml !!}
                </div>
            </article>
        </div>
    </div>
@endsection
