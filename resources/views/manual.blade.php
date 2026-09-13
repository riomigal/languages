@extends('languages::component.table-section')
@section('content')
    <div class="py-6 sm:py-8">
        <header class="card bg-base-100 shadow-sm overflow-hidden">
            <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-primary/10 blur-2xl"></div>
            <div class="card-body relative">
                <p class="badge badge-primary badge-outline">Documentation</p>
                <h1 class="card-title mt-2 text-3xl">{{ __('languages::navbar.manual') }}</h1>
                <p class="mt-3 max-w-3xl text-sm text-base-content/70">
                    Operational guide for package users. Use the quick links to jump directly to the sections you need.
                </p>
                <div class="card-actions mt-5">
                    <a href="#languages" class="btn btn-primary btn-outline btn-sm">Languages</a>
                    <a href="#translators" class="btn btn-primary btn-outline btn-sm">Translators</a>
                    <a href="#settings" class="btn btn-primary btn-outline btn-sm">Settings</a>
                </div>
            </div>
        </header>

        <div class="mt-6 grid gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                <div class="card card-body bg-base-100 p-4 shadow-sm xl:sticky xl:top-4">
                    <h2 class="menu-title px-0">Sections</h2>
                    @if(count($manualSections))
                        <ul class="menu mt-3 w-full p-0">
                            @foreach($manualSections as $section)
                                <li>
                                    <a
                                        href="#{{ $section['id'] }}"
                                        class="{{ $section['level'] === 3 ? 'ml-3 text-base-content/70' : 'font-medium' }}"
                                    >
                                        {{ $section['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-base-content/70">No sections available.</p>
                    @endif
                </div>
            </aside>

            <article class="card card-body min-w-0 xl:col-span-9 bg-base-100 p-5 shadow-sm sm:p-6">
                <div class="prose max-w-none dark:prose-invert
                            prose-headings:font-bold prose-headings:tracking-tight
                            prose-h2:mt-10 prose-h2:border-t prose-h2:border-base-300 prose-h2:pt-6 prose-h2:text-2xl prose-h2:scroll-mt-24
                            prose-h3:mt-6 prose-h3:text-xl prose-h3:scroll-mt-24
                            prose-p:leading-7 prose-li:leading-7
                            prose-a:font-semibold prose-a:text-primary
                            prose-code:rounded prose-code:bg-base-200 prose-code:px-1.5 prose-code:py-0.5
                            prose-pre:rounded-box prose-pre:bg-base-200 prose-pre:text-base-content
                            prose-blockquote:rounded-box prose-blockquote:border-primary prose-blockquote:bg-base-200 prose-blockquote:px-4 prose-blockquote:py-2
                            prose-hr:border-base-300">
                    {!! $manualHtml !!}
                </div>
            </article>
        </div>
    </div>
@endsection
