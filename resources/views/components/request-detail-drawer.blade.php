<div
    x-cloak
    x-show="open"
    class="request-detail-overlay"
    x-transition.opacity.duration.250ms
    @keydown.escape.window="close()"
>
    <div class="request-detail-backdrop" @click="close()"></div>

    <aside
        class="request-detail-panel"
        :class="panelVariantClass()"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-[104%] opacity-0 scale-[0.985]"
        x-transition:enter-end="translate-x-0 opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0 opacity-100 scale-100"
        x-transition:leave-end="translate-x-[104%] opacity-0 scale-[0.985]"
        @click.stop
    >
        <div class="request-detail-head">
            <div class="request-detail-head-copy">
                <div class="request-detail-eyebrow" x-text="item?.drawer_title || 'Ātrais skats'"></div>
                <h2 class="request-detail-title" x-text="item?.hero_title || item?.device_name || item?.primary_value || 'Nav pieejams nosaukums'"></h2>
                <p class="request-detail-subtitle" x-text="item?.drawer_subtitle || 'Galvenā informācija par izvēlēto ierakstu.'"></p>
            </div>

            <button type="button" class="request-detail-close" @click="close()" aria-label="Aizvērt ātro skatu">
                <x-icon name="x-mark" size="h-5 w-5" />
            </button>
        </div>

        <template x-if="item">
            <div class="request-detail-body">
                <section class="request-detail-hero">
                    <div
                        class="request-detail-hero-mark"
                        :class="item?.hero_tone ? `request-detail-icon-tone-${item.hero_tone}` : ''"
                    >
                        <span x-html="iconSvg(item?.hero_icon || 'view')"></span>
                    </div>

                    <div class="request-detail-hero-copy">
                        <div class="request-detail-hero-title" x-text="item.hero_title || item.device_name || item.primary_value || '-'"></div>
                        <div class="request-detail-hero-meta" x-text="item.hero_meta || item.primary_meta || item.device_serial || 'Papildu informācija nav pieejama'"></div>
                        <template x-if="item.hero_note || item.primary_note">
                            <div class="request-detail-hero-note" x-text="item.hero_note || item.primary_note"></div>
                        </template>
                    </div>
                </section>

                <section class="request-detail-summary-grid">
                    <template x-for="(summary, index) in summaryItems()" :key="`summary-${index}`">
                        <article class="request-detail-summary-card">
                            <div class="request-detail-summary-head">
                                <span
                                    class="request-detail-card-icon"
                                    :class="summary?.tone ? `request-detail-icon-tone-${summary.tone}` : ''"
                                >
                                    <span x-html="iconSvg(summary?.icon || 'information-circle')"></span>
                                </span>
                                <div class="request-detail-summary-label" x-text="summary.label || 'Kopsavilkums'"></div>
                            </div>

                            <template x-if="summary.badgeClass">
                                <span class="request-detail-status mt-3" :class="summary.badgeClass" x-text="summary.value || '-'"></span>
                            </template>

                            <template x-if="!summary.badgeClass">
                                <div class="request-detail-summary-value" x-text="summary.value || '-'"></div>
                            </template>
                        </article>
                    </template>
                </section>

                <section class="request-detail-grid">
                    <template x-for="(card, index) in infoCards()" :key="`card-${index}`">
                        <article class="request-detail-card">
                            <div class="request-detail-card-head">
                                <span
                                    class="request-detail-card-icon"
                                    :class="card?.tone ? `request-detail-icon-tone-${card.tone}` : ''"
                                >
                                    <span x-html="iconSvg(card?.icon || 'information-circle')"></span>
                                </span>
                                <div class="request-detail-card-label" x-text="card.label || 'Informācija'"></div>
                            </div>

                            <div class="request-detail-card-value" x-text="card.value || '-'"></div>
                            <template x-if="card.meta">
                                <div class="request-detail-card-meta" x-text="card.meta"></div>
                            </template>

                            <template x-if="card.notes.length > 0">
                                <div class="request-detail-card-stack">
                                    <template x-for="(note, noteIndex) in card.notes" :key="`note-${index}-${noteIndex}`">
                                        <div class="request-detail-card-note" x-text="note"></div>
                                    </template>
                                </div>
                            </template>
                        </article>
                    </template>
                </section>

                <template x-if="item.primary_link_url || item.device_url">
                    <section class="request-detail-inline-action">
                        <a :href="item.primary_link_url || item.device_url" class="request-detail-link">
                            <x-icon name="view" size="h-4 w-4" />
                            <span x-text="item.primary_link_label || 'Atvērt saistīto ierakstu'"></span>
                        </a>
                    </section>
                </template>

                <section class="request-detail-card request-detail-card-wide">
                    <div class="request-detail-card-head">
                        <span
                            class="request-detail-card-icon"
                            :class="item?.description_tone ? `request-detail-icon-tone-${item.description_tone}` : ''"
                        >
                            <span x-html="iconSvg(item?.description_icon || 'repair-request')"></span>
                        </span>
                        <div class="request-detail-card-label" x-text="item.description_label || 'Apraksts'"></div>
                    </div>
                    <div class="request-detail-copy" x-text="item.description || 'Apraksts nav norādīts.'"></div>
                </section>

                <template x-if="item && (item.details_intro || item.details_body || item.review_notes || item.reviewed_by_name)">
                    <section class="request-detail-card request-detail-card-wide request-detail-card-muted">
                        <div class="request-detail-card-head">
                            <span
                                class="request-detail-card-icon"
                                :class="item?.details_tone ? `request-detail-icon-tone-${item.details_tone}` : ''"
                            >
                                <span x-html="iconSvg(item?.details_icon || 'check-circle')"></span>
                            </span>
                            <div class="request-detail-card-label" x-text="item.details_title || 'Papildinformācija'"></div>
                        </div>

                        <div class="request-detail-detail-block">
                            <template x-if="item.details_intro || item.reviewed_by_name">
                                <div class="request-detail-detail-highlight">
                                    <span class="request-detail-detail-label" x-text="item.details_intro_label || 'Svarīgākais'"></span>
                                    <span class="request-detail-detail-value" x-text="item.details_intro || item.reviewed_by_name"></span>
                                </div>
                            </template>

                            <template x-if="textLines(item.details_body || item.review_notes).length > 0">
                                <div class="request-detail-detail-list">
                                    <template x-for="(line, lineIndex) in textLines(item.details_body || item.review_notes)" :key="`detail-line-${lineIndex}`">
                                        <div class="request-detail-detail-row">
                                            <span class="request-detail-detail-dot"></span>
                                            <span x-text="line"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <template x-if="item.details_link_url">
                            <a :href="item.details_link_url" class="request-detail-link request-detail-link-inline">
                                <span x-html="iconSvg(item.details_link_icon || 'view')"></span>
                                <span x-text="item.details_link_label || 'Atvērt saistīto ierakstu'"></span>
                            </a>
                        </template>
                    </section>
                </template>
            </div>
        </template>

        <div class="request-detail-foot">
            <button type="button" class="btn-clear" @click="close()">
                <x-icon name="clear" size="h-4 w-4" />
                <span>Aizvērt skatu</span>
            </button>
        </div>
    </aside>
</div>
