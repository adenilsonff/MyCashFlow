"""Read-only PDF checks and contact sheets for the synthetic report fixtures."""
from pathlib import Path
import json, subprocess
from pypdf import PdfReader
from PIL import Image, ImageDraw

out=Path(__file__).resolve().parents[2]/'resultados'/'relatorios'
poppler=Path.home()/'.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin/pdftoppm.exe'
if not poppler.exists():
    poppler=Path('C:/Users/IFSP/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin/pdftoppm.exe')
results=[]
for name in ['financeiro-resumido','financeiro-detalhado','grafico-horizontal','grafico-empilhadas','grafico-linhas','grafico-area']:
    file=out/(name+'.pdf');reader=PdfReader(file);texts=[p.extract_text() for p in reader.pages]
    assert all(f'Página {i+1} de {len(texts)}' in text for i,text in enumerate(texts)), name+' page numbers'
    assert '100.800,00' in '\n'.join(texts), name+' expected revenue'
    assert '69.250,44' in '\n'.join(texts), name+' exact cents'
    assert 'Sem dados' in '\n'.join(texts), name+' absent month'
    if name=='financeiro-detalhado':
        assert sum(text.count('Lançamento fictício') for text in texts)==576, 'all detailed records'
        for text in texts:
            if 'Lançamento fictício' in text:
                assert 'Titular' in text and 'Situação atual' in text, 'repeated headers'
    prefix=out/name
    subprocess.run([str(poppler),'-scale-to','750','-png',str(file),str(prefix)],check=True,capture_output=True)
    pages=sorted(p for p in out.glob(name+'-*.png') if p.stem[len(name)+1:].isdigit())
    for start in range(0,len(pages),9):
        batch=pages[start:start+9];sheet=Image.new('RGB',(1500,3*555),'#dddddd');draw=ImageDraw.Draw(sheet)
        for i,page in enumerate(batch):
            im=Image.open(page);im.thumbnail((490,515));x=(i%3)*500;y=(i//3)*555
            sheet.paste(im,(x,y+25));draw.text((x+5,y+5),page.name,fill='black')
        sheet.save(out/(name+'-contact-'+str(start//9+1)+'.png'))
    results.append({'file':file.name,'pages':len(reader.pages),'checks':'passed'})
# The exported snapshot must retain pre-edit totals, not the later 999999 value.
snap=out/'http-snapshot.pdf'
if snap.exists():
    text='\n'.join(p.extract_text() for p in PdfReader(snap).pages)
    assert '999.999,00' not in text, 'snapshot unexpectedly re-queried after edit'
    # 7 populated months x sum(1001..1008), plus original 1111 fixture.
    assert '57.363,00' in text, 'snapshot selected revenue before edit'
json.dump(results,open(out/'pdf-qa.json','w',encoding='utf8'),ensure_ascii=False,indent=2)
print(json.dumps(results,ensure_ascii=False))
