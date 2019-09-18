# README #

Решение тестового задания для компании funpay

# Постановка задачи #

#### Условия

http://funpay.ru/temp/task.zip

Требуется проанализировать класс QiwiPaymentSystem и выявить возможные ошибки. 

Искать ошибки в других местах не нужно. 

Пример использования и некоторые неочевидные тонкости можно найти в index.php. 

В целом система используется для 
- автоматической отправки денег пользователям, 
- обслуживает очередь заявок. 
:
Делается вывод, возвращается статус. 

(* Если ошибка, то повторные выводы, пока не отправится. *)

В index.php всё намного проще, но он дан исключительно для примера (нет повторов при ошибке и всего остального).


### Как развернуть проект? ###

Для разворачивания проекта необходимо клонировать или скачать репозиторий и разместить его в директории доступной для обработки вашим веб-сервером, аналогично разворачиванию любого небольшого сайта.

### Ссылка на лайв решение ###

https://2291.ru/tmp/funpay2/

### Результаты анализа исходной версии платёжной системы ###


0. Ожидаемых было явных синтаксических и бросающихся в глаза сходу алгоритмических ошибок не было обнаружено.


1. Ввод средств - не реализован. Возможно это так и надо в рамках задачи, но возможно здесь ошибка, заключающаяся в том, что не весь необходимый в бою функционал платёжной системы реализован.

    1.1. shopId всегда 0, нигде нет возможности задать аккаунт ввода средств:

        @16     private $shopId = 0;
        @35     public function getInputAccount()
        @39         return $this->shopId;

    1.2. $this->input всегда false, getInputAccount будет всегда бросать PHRASE_INPUT_DISABLED:

        @35     public function getInputAccount()
        @37         $this->requireActiveInput();



2. Реализация активных кошельков вывода сомнительна.

    2.1. Отсутствует валидация списка кошельков вывода при их задании:

        @24     public function setWallets(array $wallets)
        @26         $this->activeWallets = $wallets;

    2.2. Возможность работы вывода средств мало того что устанавливается на полностью не валидированных данных, так ещё даже если переданный массив кошельков формально был бы валидным, - не факт что хоть один кошелёк имеет хоть какие-то средства и вообще действительно существует в QIWI и кроме прочего не заблокирован.

        @24     public function setWallets(array $wallets)
        @27         $this->output = (bool)$wallets;

    2.3. Логическая ошибка выбора совсем случайного аккаунта вывода для списания. Получаем отказ в выводе на ровном месте в случае недостатка средств на выбранном кошельке при наличии достаточных для операции средств на других кошельках вывода.

        @75             $login = array_rand($this->activeWallets);

    2.4. То что апи инстанс кэшируется это хорошо, но в случае если например у данного аккаунта сменится на внешней стороне пароль, кошелёк будет удалён, или иным образом недоступен, должна быть предусмотрена обработка соответствующих ошибок и сброс апи инстанса, и, надо полагать, сброс кошелька из числа активных.


3. Решение по неопределённым ситуациям (произошёл трансфер или нет) ввиде выставления статуса трансфера Success в коде воспринимается непрозрачно (при последующем одновременно выставлении и статуса Failure) и может привести к ошибкам в бизнес логике?


4. В функции withdraw в классе никак не используется $withdrawalId (пока?) - это может при логике повторных попыток вывода привести к дублированию вывода средств по одной и той же внутренней транзакции.


5. В функции withdraw не используется параметр валюты, и если передана "неправильная", нерублёвая валюта, то напрашивается вероятность появления ошибок логики межвалютного трансфера ($100 => 100Р). Вероятно следует валидировать параметр валюты.




### Затраченное время ###

порядка 8 часов с микроскопом; не факт что найдены все проблемы, в том числе не исключая эффекта слона на самом видом месте.
