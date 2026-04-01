<div
    x-cloak
    x-show="open"
    class="request-detail-overlay"
    x-transition.opacity
    @keydown.escape.window="close()"
>
    <div class="request-detail-backdrop" @click="close()"></div>

    <aside class="request-detail-panel" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full opacity-0" x-transition:enter-end="translate-x-0 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100" x-transition:leave-end="translate-x-full opacity-0">
        <div class="request-detail-head">
            <div>
                <div class="request-detail-eyebrow" x-text="item?.drawer_title || 'Pieteikuma detaļas'"></div>
                <h2 class="request-detail-title" x-text="item?.device_name || 'Nav pieejams nosaukums'"></h2>
                <p class="request-detail-subtitle" x-text="item?.drawer_subtitle || 'Ātrais skats ar galveno informāciju par ierakstu.'"></p>
            </div>

            <button type="button" class="request-detail-close" @click="close()">
                <x-icon name="x-mark" size="h-5 w-5" />
            </button>
        </div>

        <template x-if="item">
            <div class="request-detail-body">
                <div class="request-detail-summary">
                    <div class="request-detail-status-wrap">
                        <span class="request-detail-status" :class="item.status_badge_class || ''" x-text="item.status_label || '-'"></span>
                        <span class="request-detail-date" x-text="item.submitted_at || '-'"></span>
                    </div>

                    <template x-if="item.primary_link_url || item.device_url">
                        <a :href="item.primary_link_url || item.device_url" class="request-detail-link">
                            <x-icon name="device" size="h-4 w-4" />
                            <span x-text="item.primary_link_label || 'Atvērt saistīto ierīci'"></span>
                        </a>
                    </template>
                </div>

                <div class="request-detail-grid">
                    <div class="request-detail-card">
                        <div class="request-detail-card-label" x-text="item.primary_label || 'Ierīce'"></div>
                        <div class="request-detail-card-value" x-text="item.primary_value || item.device_code || '-'"></div>
                        <div class="request-detail-card-meta" x-text="item.primary_meta || item.device_serial || 'Papildu informācija nav pieejama'"></div>
                        <div class="request-detail-card-note" x-text="item.primary_note || item.device_meta || ''"></div>
                        <div class="request-detail-card-note" x-text="item.primary_note_secondary || item.device_type || ''"></div>
                    </div>

                    <div class="request-detail-card">
                        <div class="request-detail-card-label" x-text="item.secondary_label || 'Pieteicējs'"></div>
                        <div class="request-detail-card-value" x-text="item.secondary_value || item.requester_name || '-'"></div>
                        <div class="request-detail-card-meta" x-text="item.secondary_meta || item.requester_meta || 'Papildu informācija nav pieejama'"></div>
                        <div class="request-detail-card-note" x-text="item.secondary_note || ''"></div>
                    </div>

                    <template x-if="item.tertiary_value || item.recipient_name">
                        <div class="request-detail-card">
                            <div class="request-detail-card-label" x-text="item.tertiary_label || 'Saņēmējs'"></div>
                            <div class="request-detail-card-value" x-text="item.tertiary_value || item.recipient_name"></div>
                            <div class="request-detail-card-meta" x-text="item.tertiary_meta || item.recipient_meta || 'Papildu informācija nav pieejama'"></div>
                            <div class="request-detail-card-note" x-text="item.tertiary_note || ''"></div>
                        </div>
                    </template>
                </div>

                <div class="request-detail-card request-detail-card-wide">
                    <div class="request-detail-card-label" x-text="item.description_label || 'Apraksts'"></div>
                    <div class="request-detail-copy" x-text="item.description || 'Apraksts nav norādīts.'"></div>
                </div>

                <template x-if="item && (item.review_notes || item.reviewed_by_name)">
                    <div class="request-detail-card request-detail-card-wide">
                        <div class="request-detail-card-label">Izskatīšanas informācija</div>
                        <div class="request-detail-copy">
                            <template x-if="item.reviewed_by_name">
                                <div><strong>Izskatīja:</strong> <span x-text="item.reviewed_by_name"></span></div>
                            </template>
                            <template x-if="item.review_notes">
                                <div class="mt-2" x-text="item.review_notes"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </aside>
</div>
