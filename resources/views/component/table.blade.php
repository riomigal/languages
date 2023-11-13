<div class="relative overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
        <tr>
            @foreach($thead as $field)
                <th scope="col" class="px-4 py-3">{{$field}}</th>
            @endforeach
            <th scope="col" class="px-4 py-3">
                <span class="sr-only">{{__('languages::table.actions')}}</span>
            </th>
        </tr>
        </thead>
        <tbody>
        @if(isset($action))
            @php
                $action  = array_flip($action);
            @endphp
        @endif
        @foreach($data as $item)
            <tr class="responsive border-b dark:border-gray-700">
                @foreach($tbody as $key => $field)
                    <td class="responsive px-4 py-3">
                        <span class="responsive-th font-bold">{!! $thead[$key] . ':&nbsp;' !!}</span>
                        <span>
                        @if(isset($relations[$field]))
                                {{ implode(', ', $item->{$field}()->pluck($relations[$field])->all()) }}
                            @elseif(is_bool($item->{$field}))
                                @include('languages::component.boolean-icon', ['boolean' =>$item->{$field}])
                            @else
                                {{ $item->{$field} }}
                            @endif
                        </span>
                    </td>
                @endforeach
                @if(isset($action) || isset($route))
                    <td
                            class="responsive px-4 py-3 flex items-center justify-end">
                        <ul class="flex flex-wrap py-1 text-sm text-gray-700 dark:text-gray-200">
                            @if($isAdministrator)
                                @if(isset($action['needs_translation']) && !$item->needs_translation)
                                    <li>
                                        <a wire:click.prevent="requestTranslation({{$item->id}})"
                                           class="block cursor-pointer py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{__('languages::translations.table.action.needs_translation')}}</a>
                                    </li>
                                @endif
                                @if(isset($action['restore_translation']) && !$item->approved && $item->old_content)
                                    <li>
                                        <a wire:click.prevent="restoreTranslation({{$item->id}})"
                                           class="block cursor-pointer py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{__('languages::translations.table.action.restore_translation')}}</a>
                                    </li>
                                @endif
                            @endif
                            @if(isset($action['translate']))
                                <li>
                                    <a wire:click.prevent="showTranslateModal({{$item->id}})"
                                       class="block cursor-pointer py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{__('languages::translations.table.action.translate')}}</a>
                                </li>
                            @endif
                            @if(isset($route) && $route)
                                <li>
                                    <a href="{{ route($route['name'], [$route['parameter'] => $item]) }}"
                                       class="block py-2 cursor-pointer px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{__('languages::table.view')}}</a>
                                </li>
                            @endif
                            @if($isAdministrator)
                                @if(isset($action['approve_translation']) && !$item->approved && $item->value)
                                    <li>
                                        <button wire:click.prevent="approveTranslation({{$item->id}})" type="button" class="text-white bg-green-700 hover:bg-green-800 focus:ring-green-300  dark:bg-green-600 dark:hover:bg-green-700  dark:focus:ring-green-800 focus:ring-4 font-medium rounded-lg text-xs px-5 py-2.5 mr-2 mb-2 p-1 focus:outline-none">{{__('languages::translations.table.action.approve')}}</button>
                                    </li>
                                @endif
                                @if(isset($action['edit']))
                                    <li>
                                        <a wire:click.prevent="showForm({{$item->id}})"
                                           class="block cursor-pointer py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{__('languages::table.edit')}}</a>
                                    </li>
                                @endif
                                @if(isset($action['delete']))
                                    <li>
                                        <a wire:click.prevent="delete({{$item->id}})"
                                           class="block cursor-pointer py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{__('languages::table.delete')}}</a>
                                    </li>
                                @endif
                            @endif
                        </ul>
                    </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    @pushonce('styles')
        <style>
            .responsive-th {
                display: none;
            }

            @media only screen and (max-width: 768px) {
                td.responsive {
                    display: flex;
                    margin: 1px;
                    padding: 1px;
                    font-size: 1.1rem
                }

                .responsive-th {
                    display: block;
                }

                thead {
                    display: none;
                }
            }
        </style>
    @endpushonce
</div>
