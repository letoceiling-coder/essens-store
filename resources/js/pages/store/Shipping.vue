<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6">Доставка и оплата</h1>
                
                <div class="prose max-w-none space-y-8">
                    <section>
                        <h2 class="text-2xl font-semibold mb-4">Способы доставки</h2>
                        <div class="space-y-4">
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Курьерская доставка</h3>
                                <p class="text-muted-foreground mb-2">
                                    Доставка курьером по Москве и Московской области в течение 1-2 рабочих дней.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Стоимость: 300 ₽ (бесплатно при заказе от 3000 ₽)
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Почта России</h3>
                                <p class="text-muted-foreground mb-2">
                                    Доставка по всей России через Почту России. Срок доставки: 5-14 рабочих дней.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Стоимость: от 250 ₽ (зависит от веса и региона)
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Транспортные компании</h3>
                                <p class="text-muted-foreground mb-2">
                                    Доставка через СДЭК, Boxberry и другие транспортные компании.
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Стоимость рассчитывается индивидуально
                                </p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">Способы оплаты</h2>
                        <div class="space-y-4">
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Банковской картой онлайн</h3>
                                <p class="text-muted-foreground">
                                    Оплата банковской картой Visa, MasterCard, МИР через защищенный платежный шлюз.
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Наложенный платеж</h3>
                                <p class="text-muted-foreground">
                                    Оплата при получении заказа на почте или у курьера (только для доставки по России).
                                </p>
                            </div>
                            <div class="bg-card p-6 rounded-lg border border-border">
                                <h3 class="font-semibold mb-2">Электронные кошельки</h3>
                                <p class="text-muted-foreground">
                                    Оплата через Яндекс.Кассу, Сбербанк Онлайн и другие электронные платежные системы.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">Возврат товара</h2>
                        <p class="text-muted-foreground mb-4">
                            Вы можете вернуть товар в течение 14 дней с момента получения, если он не был в употреблении, 
                            сохранены его товарный вид, потребительские свойства, пломбы, фабричные ярлыки.
                        </p>
                        <p class="text-muted-foreground">
                            Для возврата товара свяжитесь с нами по телефону или email, указанным в разделе "Контакты".
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { onMounted } from 'vue';
import StoreLayout from '@/layouts/StoreLayout.vue';
import { setMetaTags, getBaseUrl } from '@/utils/seo';

export default {
    name: 'Shipping',
    components: {
        StoreLayout,
    },
    setup() {
        onMounted(() => {
            const baseUrl = getBaseUrl();
            setMetaTags({
                title: 'Доставка и оплата — Essens',
                description: 'Информация о доставке и оплате в интернет-магазине Essens. Способы доставки, сроки, стоимость. Варианты оплаты заказа.',
                keywords: 'доставка, оплата, Essens, способы доставки, способы оплаты',
                url: `${baseUrl}/shipping`,
                type: 'website',
            });
        });
    },
};
</script>


