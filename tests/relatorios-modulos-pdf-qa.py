from pathlib import Path
import subprocess,json
from pypdf import PdfReader
from PIL import Image,ImageDraw
out=Path(__file__).resolve().parents[2]/'resultados/relatorios-etapa2'
poppler=Path('C:/Users/IFSP/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin/pdftoppm.exe')
results=[];images=[]
for mod,total,count in [('gastos','1.345,50',35),('receitas','11.111,00',34),('cartao','750,00',66)]:
    file=out/(mod+'-detalhado.pdf');r=PdfReader(file);texts=[p.extract_text() for p in r.pages];text='\n'.join(texts)
    assert total in text,(mod,'total')
    assert 'Sem dados' in text
    assert all(f'Página {i+1} de {len(texts)}' in t for i,t in enumerate(texts))
    marker={'gastos':'GASTO_QA_','receitas':'RENDA_QA_','cartao':'COMPRA_QA_'}[mod]
    expected=66 if mod=='cartao' else 33
    assert text.count(marker)==expected,(mod,text.count(marker))
    for t in texts:
        if marker in t: assert 'Titular' in t and 'Situação atual' in t
    subprocess.run([str(poppler),'-scale-to','1000','-png',str(file),str(out/mod)],check=True,capture_output=True)
    images.extend(sorted(out.glob(mod+'-[0-9]*.png')))
    results.append({'module':mod,'pages':len(texts),'passed':True})
snap='\n'.join(p.extract_text() for p in PdfReader(out/'cartao-snapshot.pdf').pages)
assert '750,00' in snap and '999.999,00' not in snap
for start in range(0,len(images),6):
    sheet=Image.new('RGB',(1500,1120),'#ddd');draw=ImageDraw.Draw(sheet)
    for i,p in enumerate(images[start:start+6]):
        im=Image.open(p);im.thumbnail((490,520));x=i%3*500;y=i//3*560;sheet.paste(im,(x,y+25));draw.text((x+5,y+5),p.name,fill='black')
    sheet.save(out/('pdf-contato-'+str(start//6+1)+'.png'))
(out/'pdf-qa.json').write_text(json.dumps(results,indent=2),encoding='utf8')
print(results)
