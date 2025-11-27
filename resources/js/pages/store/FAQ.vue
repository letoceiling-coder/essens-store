<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6">Часто задаваемые вопросы</h1>
                
                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-card rounded-lg border border-border overflow-hidden"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-muted/50 transition-colors"
                        >
                            <span class="font-semibold">{{ faq.question }}</span>
                            <svg
                                :class="['w-5 h-5 transition-transform', openFaqs[index] ? 'rotate-180' : '']"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div
                            v-show="openFaqs[index]"
                            class="px-6 pb-4 text-muted-foreground"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>

                <!-- Contact Section -->
                <div class="mt-12 bg-card rounded-lg border border-border p-6 text-center">
                    <h2 class="text-xl font-semibold mb-2">Не нашли ответ на свой вопрос?</h2>
                    <p class="text-muted-foreground mb-4">
                        Свяжитесь с нами, и мы с радостью поможем вам
                    </p>
                    <router-link
                        to="/contacts"
                        class="inline-flex items-center px-6 py-3 bg-primary text-primary-contrast rounded-lg font-medium hover:opacity-90 transition-opacity"
                    >
                        Связаться с нами
                    </router-link>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { ref, onMounted } from 'vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { setMetaTags, addStructuredData, getBaseUrl } from '@/utils/seo';

export default {
    name: 'FAQ',
    components: {
        StoreLayout,
    },
    setup() {
        const openFaqs = ref({});
        const faqs = [
            {
                question: 'Как оформить заказ?',
                answer: 'Выберите товары, добавьте их в корзину, перейдите к оформлению заказа и заполните необходимые данные. После подтверждения заказа мы свяжемся с вами для уточнения деталей.',
            },
            {
                question: 'Какие способы оплаты доступны?',
                answer: 'Мы принимаем оплату банковскими картами Visa, MasterCard, МИР, наложенным платежом при получении, а также через электронные платежные системы.',
            },
            {
                question: 'Сколько стоит доставка?',
                answer: 'Стоимость доставки зависит от выбранного способа и региона. При заказе от 3000 ₽ доставка по Москве бесплатная. Подробную информацию смотрите в разделе "Доставка и оплата".',
            },
            {
                question: 'Как долго ждать доставку?',
                answer: 'Срок доставки зависит от региона и выбранного способа доставки. По Москве — 1-2 рабочих дня, по России через Почту России — 5-14 рабочих дней.',
            },
            {
                question: 'Можно ли вернуть товар?',
                answer: 'Да, вы можете вернуть товар надлежащего качества в течение 14 дней с момента получения, если он не был в употреблении и сохранены все его свойства.',
            },
            {
                question: 'Где можно посмотреть состав товара?',
                answer: 'Состав каждого товара указан на его странице в разделе "Характеристики" или "Описание".',
            },
            {
                question: 'Есть ли гарантия на товары?',
                answer: 'Все товары имеют гарантию качества производителя. При обнаружении брака или несоответствия описанию вы можете вернуть товар или обменять его.',
            },
            {
                question: 'Как связаться с поддержкой?',
                answer: 'Вы можете связаться с нами по телефону +7 (999) 123-45-67, email info@essens.ru или через форму обратной связи в разделе "Контакты".',
            },
        ];

        const toggleFaq = (index) => {
            openFaqs.value[index] = !openFaqs.value[index];
        };

        onMounted(() => {
            const baseUrl = getBaseUrl();
            setMetaTags({
                title: 'Часто задаваемые вопросы — Essens',
                description: 'Ответы на часто задаваемые вопросы об интернет-магазине Essens. Информация о заказе, доставке, оплате, возврате товаров.',
                keywords: 'FAQ, часто задаваемые вопросы, Essens, помощь, поддержка',
                url: `${baseUrl}/faq`,
                type: 'website',
            });

            // Структурированные данные для FAQ
            const faqSchema = {
                '@context': 'https://schema.org',
                '@type': 'FAQPage',
                mainEntity: faqs.map(faq => ({
                    '@type': 'Question',
                    name: faq.question,
                    acceptedAnswer: {
                        '@type': 'Answer',
                        text: faq.answer,
                    },
                })),
            };
            addStructuredData(faqSchema);
        });

        return {
            faqs,
            openFaqs,
            toggleFaq,
        };
    },
};
</script>


