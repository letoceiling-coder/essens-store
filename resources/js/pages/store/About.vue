<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6">О компании Essens</h1>
                
                <div class="prose max-w-none">
                    <section class="mb-12">
                        <h2 class="text-2xl font-semibold mb-4">Наша миссия</h2>
                        <p class="text-muted-foreground leading-relaxed mb-4">
                            Essens — это интернет-магазин, специализирующийся на продаже качественной продукции 
                            для здоровья и красоты. Мы верим, что каждый человек заслуживает доступа к натуральным 
                            и эффективным продуктам, которые помогут поддерживать здоровье и красоту.
                        </p>
                        <p class="text-muted-foreground leading-relaxed">
                            Наша миссия — сделать качественную продукцию для здоровья доступной каждому, 
                            обеспечивая при этом высокий уровень сервиса и гарантию качества.
                        </p>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-2xl font-semibold mb-4">Почему выбирают нас</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Качество продукции</h3>
                                <p class="text-muted-foreground text-sm">
                                    Все товары проходят строгий контроль качества и имеют необходимые сертификаты
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Натуральные ингредиенты</h3>
                                <p class="text-muted-foreground text-sm">
                                    Мы работаем только с проверенными поставщиками натуральных компонентов
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Быстрая доставка</h3>
                                <p class="text-muted-foreground text-sm">
                                    Доставляем заказы по всей России в кратчайшие сроки
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Профессиональная поддержка</h3>
                                <p class="text-muted-foreground text-sm">
                                    Наша команда всегда готова помочь с выбором и ответить на ваши вопросы
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="mb-12">
                        <h2 class="text-2xl font-semibold mb-4">Наши ценности</h2>
                        <ul class="space-y-3 text-muted-foreground">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Честность и прозрачность во всех аспектах работы</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Забота о здоровье и благополучии наших клиентов</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Постоянное развитие и улучшение сервиса</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-primary mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Ответственность перед обществом и окружающей средой</span>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { onMounted } from 'vue';
import StoreLayout from '@/layouts/StoreLayout.vue';

export default {
    name: 'About',
    components: {
        StoreLayout,
    },
    setup() {
        onMounted(() => {
            document.title = 'О компании Essens — Интернет-магазин продукции для здоровья';
            const metaDescription = document.querySelector('meta[name="description"]');
            if (metaDescription) {
                metaDescription.setAttribute('content', 'О компании Essens. Узнайте больше о нашей миссии, ценностях и принципах работы. Мы предлагаем качественную продукцию для здоровья и красоты.');
            }
        });
    },
};
</script>


