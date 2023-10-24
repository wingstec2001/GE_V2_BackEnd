<! DOCTYPE html>
    <!doctype html>
    <html>

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style type="text/css">
            @font-face {
                font-family: ipag;
                font-style: normal;
                font-weight: normal;
                src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
            }

            @font-face {
                font-family: ipag;
                font-style: bold;
                font-weight: bold;
                src: url('{{ storage_path('fonts/ipag.ttf') }}') format('truetype');
            }

            body {
                font-family: ipag !important;
            }
        </style>
    </head>

    <body>
        <div>
            <p align="center"> 商品売買契約書 </p>
        </div>
        <div>
            <h5> 買主株式会社 <b>{{$buyer}}</b>（以下「甲」という）と売主株式会社<b>グリーンアース坂東株式会社</b>（以下「乙」という）とは、商品の売買に関し、以下のとおり契約を締結したため、本書を２通作成し、甲乙各１通宛保管する。
        </div>
        <div>
            <h4>（基本合意）</h4>
        </div>
        <div>
            <b>第１条</b> 乙は甲に対し、別紙目録に記載する○○○○（以下「本件商品」という。）を別紙
            目録記載の価格にて甲に売り渡すことを約し、甲はこれを買い受けることを約する。
        </div>
        <div>
            <h4>（引渡し）</h4>
        </div>
        <b>
            第２条 乙は甲に対し、次のとおりの引渡場所及び納入日にて、本件商品を引き渡す。
            （引渡場所）
        </b>
        すべて甲の本店所在地
        （納入日）
        平成○○年○○月○○日 別紙目録１ないし３
        平成○○年○○月○○日 別紙目録４ないし６
        ２ 甲又は乙が納入日又は引渡場所の変更を申し出た場合には、その相手方の了承を
        得て、新たな納入日又は新たな引渡場所に変更することができる。ただし、その
        変更により費用が増額した場合には、その増額の部分は変更を申し出た者の負担
        とする。
        <div>
            （代金の支払条件）
            第３条 代金の支払条件は、次のとおりとし、甲は乙に対し、次の代金を持参又は乙の指
            定する口座に振込みの上、支払わなくてはならない。振込手数料は、甲の負担とす
            る。
            契約商品 {{$contract_product}}
            本契約締結日 {{$contract_date}}
            契約金額 {{$contract_amount}}

        </div>
    </body>

    </html>