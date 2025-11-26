<template>
    <StoreLayout>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl md:text-4xl font-bold mb-6">Пользовательское соглашение</h1>
                
                <div class="prose max-w-none space-y-6">
                    <section>
                        <p class="text-muted-foreground">
                            <strong>Дата вступления в силу:</strong> {{ currentDate }}
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">1. Общие положения</h2>
                        <p class="text-muted-foreground mb-4">
                            Настоящее Пользовательское соглашение (далее — «Соглашение») регулирует отношения между 
                            интернет-магазином Essens (далее — «Магазин») и пользователем сайта (далее — «Пользователь»).
                        </p>
                        <p class="text-muted-foreground">
                            Используя Сайт, вы соглашаетесь с условиями настоящего Соглашения.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">2. Предмет соглашения</h2>
                        <p class="text-muted-foreground mb-4">
                            Магазин предоставляет Пользователю возможность приобретать товары, представленные на Сайте, 
                            в соответствии с условиями настоящего Соглашения.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">3. Регистрация и учетная запись</h2>
                        <p class="text-muted-foreground mb-4">
                            Для оформления заказа Пользователь может пройти регистрацию на Сайте или оформить заказ 
                            без регистрации, указав необходимые контактные данные.
                        </p>
                        <p class="text-muted-foreground">
                            Пользователь несет ответственность за сохранность своих учетных данных и за все действия, 
                            совершенные под его учетной записью.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">4. Оформление заказа</h2>
                        <p class="text-muted-foreground mb-4">
                            Заказ считается оформленным после подтверждения Магазином принятия заказа к исполнению. 
                            Подтверждение заказа направляется Пользователю на указанный email или телефон.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">5. Цены и оплата</h2>
                        <p class="text-muted-foreground mb-4">
                            Все цены на товары указаны в рублях и включают НДС (если применимо). 
                            Магазин оставляет за собой право изменять цены на товары без предварительного уведомления.
                        </p>
                        <p class="text-muted-foreground">
                            Оплата заказа производится способами, указанными на Сайте в разделе «Доставка и оплата».
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">6. Доставка</h2>
                        <p class="text-muted-foreground">
                            Условия и сроки доставки товаров указаны на Сайте в разделе «Доставка и оплата». 
                            Стоимость доставки рассчитывается индивидуально в зависимости от выбранного способа доставки.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">7. Возврат товара</h2>
                        <p class="text-muted-foreground mb-4">
                            Пользователь имеет право вернуть товар надлежащего качества в течение 14 дней с момента 
                            получения, если товар не был в употреблении, сохранены его товарный вид, потребительские свойства, 
                            пломбы, фабричные ярлыки.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">8. Ответственность</h2>
                        <p class="text-muted-foreground">
                            Магазин не несет ответственности за ущерб, причиненный Пользователю в результате использования 
                            или невозможности использования Сайта, включая упущенную выгоду.
                        </p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-semibold mb-4">9. Изменения в соглашении</h2>
                        <p class="text-muted-foreground">
                            Магазин оставляет за собой право вносить изменения в настоящее Соглашение. 
                            Изменения вступают в силу с момента их публикации на Сайте.
                        </p>
                    </section>
                </div>
            </div>
        </div>
    </StoreLayout>
</template>

<script>
import { computed, onMounted } from 'vue';
import StoreLayout from '@/layouts/StoreLayout.vue';

export default {
    name: 'Terms',
    components: {
        StoreLayout,
    },
    setup() {
        const currentDate = computed(() => {
            return new Date().toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        });

        onMounted(() => {
            document.title = 'Пользовательское соглашение — Essens';
            const metaDescription = document.querySelector('meta[name="description"]');
            if (metaDescription) {
                metaDescription.setAttribute('content', 'Пользовательское соглашение интернет-магазина Essens. Условия использования сайта и оформления заказов.');
            }
        });

        return {
            currentDate,
        };
    },
};
</script>


