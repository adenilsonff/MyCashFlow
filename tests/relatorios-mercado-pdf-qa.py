from pathlib import Path
import json,subprocess
from pypdf import PdfReader
from PIL import Image,ImageDraw
out=Path(__file__).resolve().parents[2]/'resultados/relatorios-etapa3'
poppler=Path('C:/Users/IFSP/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin/pdftoppm.exe')
images=[];results=[]
for name,total,marker,count in [('investimentos-detalhado','R$ 125,00','Quantidade:',6),('proventos-detalhado','R$ 45,19','Data-com:',6),('daytrade-detalhado','R$ 25,00','CORRETORA_ALFA',9),('investimentos-usd','US$ 40,00','Quantidade:',2)]:
    file=out/(name+'.pdf');r=PdfReader(file);texts=[p.extract_text() for p in r.pages];text='\n'.join(texts)
    assert total in text,(name,'total');assert text.count(marker)==count,(name,text.count(marker));assert 'BETA3' not in text
    assert all(f'Página {i+1} de {len(texts)}' in t for i,t in enumerate(texts)),name
    if name=='investimentos-usd': assert 'R$' not in text
    if name=='proventos-detalhado': assert '0.12345678' in text and '121' in text
    subprocess.run([str(poppler),'-scale-to','1000','-png',str(file),str(out/name)],capture_output=True,check=True)
    images.extend(sorted(p for p in out.glob(name+'-*.png') if p.stem[len(name)+1:].isdigit()))
    results.append({'file':file.name,'pages':len(texts),'passed':True})
snapshot='\n'.join(p.extract_text() for p in PdfReader(out/'daytrade-snapshot.pdf').pages)
assert 'R$ 25,00' in snapshot and '99.999,00' not in snapshot
for start in range(0,len(images),6):
    sheet=Image.new('RGB',(1500,800),'#ddd');draw=ImageDraw.Draw(sheet)
    for i,p in enumerate(images[start:start+6]):
        im=Image.open(p);im.thumbnail((490,370));x=i%3*500;y=i//3*400;sheet.paste(im,(x,y+25));draw.text((x+5,y+5),p.name,fill='black')
    sheet.save(out/('pdf-contato-'+str(start//6+1)+'.png'))
(out/'pdf-qa.json').write_text(json.dumps(results,indent=2),encoding='utf8');print(results)
