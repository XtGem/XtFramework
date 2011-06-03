Current state of a variable: <?=$session -> state?><br/>
Set to:<br/>
<a href="<?=url ( '%xsrf_set', '?state=1' )?>">1</a><br/>
<a href="<?=url ( '%xsrf_set', '?state=2' )?>">2</a><br/>
<a href="<?=url ( '%xsrf_set', '?state=3' )?>">3</a><br/>
<a href="<?=url ( '%xsrf_set', '?state=foo' )?>">foo</a><br/>
<a href="<?=url ( '%xsrf_set', '?state=bar' )?>">bar</a><br/>
<a href="<?=url ( '%xsrf_set', '?state=baz' )?>">baz</a><br/>
<a href="<?=url ( 'xsrf', 'set', '?_xsrf', '?state=test' )?>">test</a><br/>