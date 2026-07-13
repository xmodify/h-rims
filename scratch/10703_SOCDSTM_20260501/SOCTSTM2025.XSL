<?xml version="1.0" encoding="Windows-874"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"   
xmlns:msxsl="urn:schemas-microsoft-com:xslt" 
xmlns:user="script:user">
<xsl:key name="keyHN" match="//HDBills/HDBill" use="hn"/>
<xsl:key name="keyHNwk" match="//HDBills/HDBill" use="concat(hn,wkno)"/>
<xsl:key name="keyEHN" match="//HDBills/HDBill" use="hn"/>
<xsl:key name="keyEHNwk" match="//HDBills/HDBill" use="concat(hn,wkno)"/>
<xsl:key name="keyLHN" match="//HDLBills/HDBill" use="hn"/>
<xsl:key name="keyLHNwk" match="//HDLBills/HDBill" use="concat(hn,wkno)"/>

<xsl:template match="/STMSTM">
<xsl:comment>Last Update : 2020-06-04</xsl:comment>
<html>
<head>
<Style type="text/css">
				body {margin-top: 5pt; margin-left: 20pt; margin-right: 10pt  }
				p{font-family:Angsana New; font-size:14pt; align: left; margin-top: -12pt}
				th{font-family:Tahoma; font-size:9pt; margin-top: -12pt;background-color: #99CCFF;}
				th.fst{font-family:Tahoma; font-size:9pt; margin-top: -12pt;background-color: #B9DCFF;}
				th.snd{font-family:Tahoma; font-size:9pt; margin-top: -12pt;background-color: #CEE7FF;}
				th.trd{font-family:Tahoma; font-size:9pt; margin-top: -12pt;background-color: #FFFFFF;}
				th.hd{font-family:Tahoma; font-size:9pt; margin-top: -12pt;background-color: #66CC99;}
				td{font-family:Tahoma; font-size:9pt; align: left; margin-top: -12pt}
				td.spl{font-family:Tahoma; font-size:8pt; color: #808080 ; align: left; margin-top: -12pt}
				td.adj{font-family:Tahoma; font-size:8pt; color: #818181 ; align: left; margin-top: -12pt}
				td.oth{font-family:Tahoma; font-size:9pt; color: #999999 ; align: left; margin-top: -12pt;background-color: #F2F2F2;}
				td.hh{font-family:Tahoma; font-size:9pt; align: left; margin-top: -12pt;background-color: #99CCFF;}
				td.epo{font-family:Tahoma; font-size:9pt; align: left; margin-top: -12pt;background-color: #E0E0E0;}
				td.sum{font-family:Angsana New; font-size:14pt; align: left; margin-top: -16pt; padding-bottom: 0 pt}
				td.sumC{font-family:Tahoma; font-size:9pt; color:#6B6B6B ; align: left; margin-top: -12pt; padding-bottom: 0 pt}
				td.sumH{font-family:Angsana New; font-size:14pt; color:#6B6B6B ; align: left; margin-top: -16pt; padding-bottom: 0 pt}
				td.sumsup{font-family:Angsana New; font-size:10pt; padding-top: 0 pt; margin-top: -16pt}
				sup{font-family:Angsana New; font-size:8pt}
				H3 {page-break-before: always}
				H4 {page-break-after: always}
				H5 {page-break-before: auto}
				thead th {
					position:relative;
				}
				thead {display: table-header-group; }
				hr.1 { margin-top: 3 pt; margin-bottom: 0 pt;   padding-top:  0 pt;    border-top: thin dotted; color: gray  }
				a.link{text-decoration: none;color: #000000}
				a:visited {text-decoration: none;color: #000000;}
				a:hover {text-decoration: none;color: #000000;}
				a:active {text-decoration: none;color: #000000;}

				
</Style>
<Style Type = "text/css" MEDIA = "print">
				.nonprint{display:none;}
</Style>
<title><xsl:value-of select="STMdoc"/></title>	
</head>
<body >
<br/>
<br/>
<p ><B><xsl:value-of select="config/STMheader/@desc"/></B></p>
<p  style="margin-top: -14pt">งวดวันที่ &#160;&#160;<xsl:value-of select="dateStart"/>&#160;&#160; ถึงวันที่ &#160;&#160;
<xsl:value-of select="dateEnd"/>
</p>
<p style="margin-top: -14pt">เลขที่เอกสาร : <B><xsl:value-of select="STMdoc"/></B>&#160;&#160;&#160;&#160;&#160;&#160;&#160;ออกโดย&#160;&#160;&#160;&#160;สำนักสารสนเทศบริการสุขภาพ&#160;&#160;&#160;&#160;<xsl:value-of select="dateIssue"/></p>
<!-- Spacial Message-->
 <xsl:variable name="Spd" select="//spacial/desc"/>	 <!--select="amount"  -->
 <xsl:variable name="Spc" select="//spacial/code"/>	
 <xsl:choose>		
	<xsl:when test="$Spd = ''">
	</xsl:when>		
	<xsl:otherwise>
<p style="margin-top: -14pt; color: red;"><xsl:value-of select="//spacial/desc"/></p>
	</xsl:otherwise>
 </xsl:choose>		
	
<!-- /Spacial Message-->	
	<p style="margin-top: -2pt"><B><xsl:value-of select="hname"/>&#160;&#160;(<xsl:value-of select="hcode"/><xsl:for-each select = "hcode/@id">&#160;:&#160;<xsl:value-of select ="."/></xsl:for-each>)</B></p>
	<p style="margin-top: -10pt">รวมยอดเบิกทั้งสิ้น &#160;&#160;&#160;<B>
	<xsl:choose>
	<xsl:when  test ="$Spc ='H'">0.00</xsl:when>
	<xsl:otherwise><xsl:value-of select='format-number(amount,"#,##0.00")'/></xsl:otherwise>
	</xsl:choose></B> &#160;&#160;บาท
</p>
<p style ="margin-top : -6pt">
	<xsl:for-each select ="STMdat[@code='HD']">
	<table width = '800' border = '0' cellspacing = '0'  cellpadding = '0'>
	<tr>
	<td colspan = '5'> <hr size ='2pt'  align = 'left' noshade ='true'/></td>
	</tr>
	<tr>
	<td CLASS = "sum" colspan = '3'><font color='006600'><xsl:value-of select="@name"/>&#160;-&#160;<B><xsl:value-of select="@desc1"/></B></font></td>
	<td CLASS = "sum" align = 'right'><B><!--><xsl:value-of select="STMdat/tst"/><!--></B></td>
	<td CLASS = "sum" align='right'><!-->สถานี<!--></td>
	</tr>
	<xsl:for-each select ='dat'>
	<tr>
	<td CLASS = "sum" width = '250'><xsl:value-of select ='@desc'/><xsl:if test ="@code='P' and count &gt; 0"><xsl:value-of select ='@desc1'/></xsl:if></td>
	<td CLASS = "sum" width = '250' align = 'right'><xsl:value-of select='format-number(count,"#,##0")'/>&#160; &#160;</td>
	<td CLASS = "sum" width = '70' >รายการ</td>
	<td CLASS = "sum" width = '190' align = 'right'><xsl:choose><xsl:when  test ="$Spc ='H'">0</xsl:when><xsl:otherwise><xsl:value-of select='format-number(total,"#,##0.00")'/></xsl:otherwise></xsl:choose></td>
	<td CLASS = "sum" width = '40' align = 'right'>บาท</td>
	</tr>
	<xsl:if test ="@code='P' and count &gt; 0">
	<tr>
	<td CLASS = "sumsup" colspan = '5' ><i>**ดูเอกสารเลขที่ &#160; &#160;<xsl:value-of select="//ADJdoc"/></i></td>
	</tr>
	</xsl:if>
	</xsl:for-each>
	<tr>
	<td CLASS = "sum" colspan = '3'> รวมยอดเบิกสำหรับรายการ<xsl:value-of select="@desc1"/>ทั้งสิ้น</td>
	<td CLASS = "sum" align = 'right'><xsl:choose><xsl:when  test ="$Spc ='H'">0</xsl:when>
	<xsl:otherwise><xsl:value-of select='format-number(amount,"#,##0.00")'/></xsl:otherwise></xsl:choose></td>
	<td CLASS = "sum" align='right'>บาท</td>
	</tr>
	<tr>
	<td colspan = '5'> <hr class = "1"/></td>
	</tr>
	</table>
	</xsl:for-each>

<xsl:comment>HD</xsl:comment>
<xsl:choose>
<xsl:when test ="//STMdat[@code='HD']/dat[@code='B']/count &gt; 0">

<table BORDER = "0" width = "800" CELLSPACING = "0" cellpadding = "0">
<tr height ='40pt'>
<td class ='sum' valign ='bottom'>
<font color ="#006600" size ='5pt'><B><xsl:value-of select="//config/Addheader[@code='H']/@desc" /></B></font>
</td>
</tr>
</table>
<table BORDER = "0" width = "800" CELLSPACING = "0" cellpadding = "0">
<thead>
<tr>
<td width ='40'>&#160;</td>
<td width ='50'>&#160;</td>
<td width ='40'>&#160;</td>
<td width ='100'>&#160;</td>
<td width ='110'>&#160;</td>
<td width ='60'>&#160;</td>
<td width ='30'>&#160;</td>
<td width ='30'>&#160;</td>
<td width ='90'>&#160;</td>
<td width ='90'>&#160;</td>
<td width = '90'>&#160;</td>
<td width = '30'>&#160;</td>
<td width = '40'>&#160;</td>
</tr>
<tr height ='28pt'>
      <th style="border-right: 1px solid #2F97FF; border-left: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >ลำดับ</th>
      <th style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center"	colspan='3' >&#160;&#160;HN </th>
      <th style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center"	 colspan ='9'>&#160;ชื่อ - สกุล </th>
</tr>
<tr height ='28pt'>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-left: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center" >&#160;</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center" colspan='5'>&#160;&#160;ลำดับสัปดาห์</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" colspan='2'>HDs</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >Quota</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >Reimbs</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >HDPay</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >&#160;</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >Wstat</th>
</tr>

<tr height ='28pt'>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-left: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="left" valign ="center" >&#160;</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-left: 1px solid; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >STM</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center">สถานี</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >InvNo</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >DTtran</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >REP</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Flag</th>	
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Clm</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Rate</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Reimb</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >PSes</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center">Ext</th>	
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center">Stat</th>	
</tr>

<tr><td colspan = "13"><hr  size = '1px'  /></td></tr>
</thead>
<tbody>
<xsl:for-each select ="/STMSTM/HDBills/HDBill[generate-id(.) = generate-id(key('keyHN',hn)[1])]">
<xsl:sort select="hn" order="ascending" /> 
<xsl:variable name="lnghdhn"><xsl:value-of select="hn" /></xsl:variable>
<xsl:variable name="lsthdhn" select="/STMSTM/HDBills/HDBill[hn=$lnghdhn and @Img ='N']" />
<tr height ='28pt' bgcolor="#99CCFF">
	<td align ='right'  ><B><xsl:value-of select="position()" /></B>&#160;</td>
	<td align="left"  colspan='3'><B>&#160;&#160;<xsl:value-of select="hn" /></B></td>
	<td align ='left' colspan='9'  ><B>&#160;&#160;<xsl:value-of select="name" /><xsl:if test ="benefit/@sub !=''">&#160;&#160;(<xsl:value-of select ="benefit/@main"/><xsl:value-of select ="benefit/@sub"/>)</xsl:if></B></td>
</tr>
<tr><td colspan = "13"><hr  size = '1px'  /></td></tr>

<xsl:for-each select="$lsthdhn[generate-id(.) = generate-id(key('keyHNwk', concat(hn,wkno))[1])]">
<xsl:sort select="wkno" order="ascending" />
<xsl:variable name ='cwkno' select ="wkno"/>
<tr height ='26pt' >
	<td></td>
	<td colspan ='5' bgcolor ='#CEE7FF'>&#160;&#160;<B><xsl:value-of select ='wkno'/>&#160;&#160;(&#160;<xsl:value-of select="/STMSTM/config/Wkno/week[@code = $cwkno]/@desc"/>&#160;)<xsl:value-of select="/STMSTM/config/Wkno/week[@code = $cwkno]/@desc1"/></B></td>
	<td align='right' colspan='2'  bgcolor ='#CEE7FF'><B><xsl:value-of select="hds" />&#160;&#160;&#160;</B></td>
	<td align='right'  bgcolor ='#CEE7FF'><B><xsl:if test ='quota/@time != "1"'>[</xsl:if><xsl:value-of select='format-number(quota,"#,##0.00")' /><xsl:if test ='quota/@time != "1"'>]</xsl:if></B>&#160;&#160;</td>
	<td align='right'  bgcolor ='#CEE7FF'><B><xsl:value-of select='format-number(hdcharge,"#,##0.00")' /></B>&#160;&#160;</td>
	<td align='right'   bgcolor ='#CEE7FF'><B><xsl:value-of select='format-number(payable,"#,##0.00")' /></B>&#160;&#160;</td>
	<td align='center'   bgcolor ='#CEE7FF' >&#160;</td>
	<td align='center'   bgcolor ='#CEE7FF' ><B>&#160;<xsl:value-of select ="stat/@code"/><xsl:value-of select ="stat/@id"/>&#160;</B></td>
</tr>
<xsl:for-each select ="TBill[@Img ='N']">
<xsl:choose>
<xsl:when test ='@self="Y"'>
<tr height ='22pt'>
<td></td>
<td align ='center'><I><xsl:if test ="accp/@code = 0 "><xsl:value-of select ="accp"/></xsl:if></I></td>
<td align='left' >&#160;<I><xsl:value-of select="station" /></I></td>
<td align='left' >&#160;<I><xsl:value-of select="invno" /></I></td>
<td align ='left'>&#160;<I><xsl:value-of select="concat(substring(dttran,9,2),'/',substring(dttran,6,2),'/',substring(dttran,3,2)+43)" />&#160;&#160;<xsl:value-of select="concat(substring(dttran,12,2),'.', substring(dttran,15,2))" /></I></td>
<td align='left' >&#160;<I><xsl:value-of select="rid" /></I></td>
<td align="center"><I><xsl:value-of select="HDflag" /></I></td>
<td align="center"><I><xsl:value-of select="paychk" /></I></td>
<td align ='right'><I><xsl:value-of select='format-number(hdrate,"#,##0.00")' /></I>&#160;&#160;</td>
<td align ='right'><I>
<xsl:choose>
<xsl:when test="hdcharge>0"><xsl:value-of select='format-number(hdcharge,"#,##0.00")' /></xsl:when >
<xsl:otherwise test ="amount=0">-</xsl:otherwise >
</xsl:choose>
</I>&#160;&#160;</td>
<td align = "right"><I>
<xsl:choose>
<xsl:when test="amount>0">
	  <xsl:value-of select='format-number(amount,"#,##0.00")' />&#160;&#160;
</xsl:when >
<xsl:otherwise test ="amount=0">
	 - &#160;&#160;
</xsl:otherwise >
</xsl:choose></I>
</td>
<td align ='center'><I><xsl:value-of select="EPOstat" /></I></td>
<td align="center"><I><xsl:value-of select="cstat" /><xsl:value-of select="pstat" /></I></td>
</tr>

<xsl:if test ="adj/@code='1'">
<tr>
<td></td>
<td></td>
<td></td>
<td class ='adj' colspan ='4'><I><xsl:value-of select ="//config/AdjContent[@code='A']/@desc"/><xsl:value-of select ="adj/@id"/></I></td>
</tr>
</xsl:if>
</xsl:when>
<xsl:otherwise>
<xsl:comment>oth</xsl:comment>
<tr height ='22pt' >
<td class ='oth' colspan='2' align ='center'><I>[<xsl:value-of select="hcode" />]</I></td>
<td class ='oth' align='left' >&#160;</td>
<td class ='oth' align='left' >&#160;</td>
<td class ='oth' align ='left' >&#160;<I><xsl:value-of select="concat(substring(dttran,9,2),'/',substring(dttran,6,2),'/',substring(dttran,3,2)+43)" />&#160;&#160;<xsl:value-of select="concat(substring(dttran,12,2),'.', substring(dttran,15,2))" /></I></td>
<td class ='oth' >&#160;</td>
<td class ='oth'  align ='center'><xsl:value-of select="HDflag" /></td>
<td class ='oth' align="center" ><I><xsl:value-of select="paychk" /></I></td>
<td class ='oth' align ='right' ><I><xsl:value-of select='format-number(hdrate,"#,##0.00")' /></I>&#160;&#160;</td>
<td class ='oth' align ='right' ><I><xsl:value-of select='format-number(hdcharge,"#,##0.00")' /></I>&#160;&#160;</td>
<td class ='oth' align = "right" ><I>
<xsl:choose>
<xsl:when test="amount>0">
	  <xsl:value-of select='format-number(amount,"#,##0.00")' />&#160;&#160;
</xsl:when >
<xsl:otherwise test ="amount=0">
	 - &#160;&#160;
</xsl:otherwise >
</xsl:choose></I>
</td>
<td class ='oth' align ='center' ><I><xsl:value-of select="EPOstat" /></I></td>
<td class ='oth' align="center" ><I><xsl:value-of select="cstat" /><xsl:value-of select="pstat" /></I></td>
</tr>
</xsl:otherwise>
</xsl:choose>
</xsl:for-each>
<tr height ='5pt'><td></td></tr>
</xsl:for-each>
<tr><td colspan = "13"><hr  size = '1px'  /></td></tr>
</xsl:for-each>
<xsl:comment>sUmMarY : HD</xsl:comment>
<xsl:variable name ='sHDcasB'  select ="//STMdat[@code='HD']/dat[@code='B' and @type ='HD']/count/@bill"/>
<xsl:variable name ='sHDamtB'  select ="//STMdat[@code='HD']/dat[@code='B' and @type ='HD']/total"/>
<xsl:variable name ='sHDcasH'  select ="//STMdat[@code='HD']/dat[@code='B' and @type ='HD']/count/@hold"/>
<xsl:variable name ='sHDamtH'  select ="//STMdat[@code='HD']/dat[@code='B' and @type ='HD']/total/@hold"/>
<xsl:variable name ='sHDcasW'  select ="//STMdat[@code='HD']/dat[@code='B' and @type ='HD']/count/@wait"/>
<xsl:variable name ='sHDamtW'  select ="//STMdat[@code='HD']/dat[@code='B' and @type ='HD']/total/@wait"/>


<tr height ='25pt'>
<td></td>
<td class ='sum' colspan = '4'><B><xsl:value-of select="//config/SumContent[@code='H' and @id ='B']/@desc" /></B></td>
<td class ='' align ='right' colspan='2'><B><xsl:value-of select ='format-number($sHDcasB,"#,##0")'/>&#160;&#160;</B></td>
<td class ='sum'  colspan ='2'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='H' and @id ='B']/@unit" /></B></td>
<td class ='sum' ><B><xsl:value-of select="//config/SumContent[@code='H' and @id ='B']/@desc1" /></B></td>
<td class ='' align ='right' colspan='2'><B><xsl:value-of select ='format-number($sHDamtB,"#,##0.00")'/>&#160;&#160;</B></td>
<td class ='sum'  colspan ='2'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='H' and @id ='B']/@unit1" /></B></td>
</tr>
<tr height ='25pt'>
<td></td>
<td class ='sumH' colspan = '4'><B><xsl:value-of select="//config/SumContent[@code='H' and @id ='H']/@desc" /></B></td>
<td class ='sumC' align ='right' colspan='2'><B><xsl:value-of select ='format-number($sHDcasH,"#,##0")'/>&#160;&#160;</B></td>
<td class ='sumH'  colspan ='2'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='H' and @id ='H']/@unit" /></B></td>
<td class ='sumH' ><B><xsl:value-of select="//config/SumContent[@code='H' and @id ='H']/@desc1" /></B></td>
<td class ='sumC' align ='right' colspan='2'>
<xsl:comment>
<B><xsl:value-of select ='format-number($sHDamtH,"#,##0.00")'/>&#160;&#160;</B>
</xsl:comment>
</td>
<td class ='sumH'  colspan ='2'>
<xsl:comment>
&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='H' and @id ='H']/@unit1" /></B>
</xsl:comment>
</td>
</tr>
<tr height ='25pt'>
<td></td>
<td class ='sumH' colspan = '4'><B><xsl:value-of select="//config/SumContent[@code='H' and @id ='W']/@desc" /></B></td>
<td class ='sumC' align ='right' colspan='2'><B><xsl:value-of select ='format-number($sHDcasW,"#,##0")'/>&#160;&#160;</B></td>
<td class ='sumH'  colspan ='2'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='H' and @id ='W']/@unit" /></B></td>
<td class ='sumH' ><B><xsl:value-of select="//config/SumContent[@code='H' and @id ='W']/@desc1" /></B></td>
<td class ='sumC' align ='right' colspan='2'>
<xsl:comment>
<B><xsl:value-of select ='format-number($sHDamtW,"#,##0.00")'/>&#160;&#160;</B>
</xsl:comment>
</td>
<td class ='sumH'  colspan ='2'>
<xsl:comment>
&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='H' and @id ='W']/@unit1" /></B>
</xsl:comment>
</td>
</tr>
<tr><td colspan='14'><hr style="border: 1px solid #A7A7A7;" /></td></tr>

</tbody>
</table>

<xsl:call-template name="ShowRemarks">
	<xsl:with-param name="lstpath" select="1" />
</xsl:call-template>

<xsl:comment>end of HD</xsl:comment>
</xsl:when>
<xsl:otherwise>
<xsl:comment>No show</xsl:comment>
</xsl:otherwise>
</xsl:choose>
</p>

<p style ="margin-top :10pt">
<xsl:comment>HD : Late</xsl:comment>
<div class ='nonprint'>
 <br/>
 <br/>
 <br/>
</div> 
<xsl:choose>
<xsl:when test ="//STMdat[@code='HD']/dat[@code='L']/count &gt; 0">
<H3></H3>
<table BORDER = "0" width = "700" CELLSPACING = "0" cellpadding = "0">
<tr><td class ='sum'>
<font color ="#006600" size ='5pt'><B><xsl:value-of select="//config/Addheader[@code='L']/@desc" /></B></font>
</td></tr>
</table>
<table BORDER = "0" width = "800" CELLSPACING = "0" cellpadding = "0">
<thead>
<tr>
<td width ='40'>&#160;</td>
<td width ='50'>&#160;</td>
<td width ='40'>&#160;</td>
<td width ='100'>&#160;</td>
<td width ='110'>&#160;</td>
<td width ='60'>&#160;</td>
<td width ='30'>&#160;</td>
<td width ='30'>&#160;</td>
<td width ='90'>&#160;</td>
<td width ='90'>&#160;</td>
<td width = '90'>&#160;</td>
<td width = "30">&#160;</td>
<td width = "40">&#160;</td>
</tr>
<tr height ='28pt'>
      <th style="border-right: 1px solid #2F97FF; border-left: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="center" valign ="center" >ลำดับ</th>
      <th style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center"	colspan='3' >&#160;&#160;HN </th>
      <th style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center"	 colspan ='9'>&#160;ชื่อ - สกุล </th>
</tr>
<tr height ='28pt'>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-left: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center" >&#160;</th>
      <th class ='snd'  style="border-right: 1px solid #2F97FF; border-bottom:hidden; border-top :  2px solid #2F97FF" align="left" valign ="center" colspan='12'>&#160;&#160;ลำดับสัปดาห์</th>
</tr>

<tr height ='28pt'>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-left: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="left" valign ="center" >&#160;</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-left: 1px solid; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >STM</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center">สถานี</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >InvNo</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >DTtran</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >REP</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Flag</th>	
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Clm</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Rate</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >Reimb</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center" >PSes</th>
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center">Ext</th>	
      <th class ='trd'  style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" align="center" valign ="center">Stat</th>	
</tr>

<tr><td colspan = "13"><hr  size = '1px'  /></td></tr>
</thead>
<tbody>
<xsl:for-each select ="/STMSTM/HDLBills/HDBill[generate-id(.) = generate-id(key('keyLHN',hn)[1])]">
<xsl:sort select="hn" order="ascending" /> 
<xsl:variable name="lnghdhn"><xsl:value-of select="hn" /></xsl:variable>
<xsl:variable name="lsthdhn" select="/STMSTM/HDLBills/HDBill[hn=$lnghdhn]" />
<tr height ='28pt' bgcolor="#99CCFF">
	<td align ='right'  ><B><xsl:value-of select="position()" /></B>&#160;</td>
	<td align="left"  colspan='3'><B>&#160;&#160;<xsl:value-of select="hn" /></B></td>
	<td align ='left' colspan='9'  ><B>&#160;&#160;<xsl:value-of select="name" /><xsl:if test ="benefit/@sub !=''">&#160;&#160;(<xsl:value-of select ="benefit/@main"/><xsl:value-of select ="benefit/@sub"/>)</xsl:if></B></td>
</tr>
<tr><td colspan = "13"><hr  size = '1px'  /></td></tr>
<xsl:for-each select="$lsthdhn[generate-id(.) = generate-id(key('keyLHNwk', concat(hn,wkno))[1])]">
<xsl:sort select="wkno" order="ascending" />
<xsl:variable name ='cwkno' select ="wkno"/>
<tr height ='26pt' >
	<td></td>
	<td colspan ='12' bgcolor ='#CEE7FF'>&#160;&#160;<B><xsl:value-of select ='wkno'/>&#160;&#160;(&#160;<xsl:value-of select="/STMSTM/config/Wkno/week[@code = $cwkno]/@desc"/>&#160;)<xsl:value-of select="/STMSTM/config/Wkno/week[@code = $cwkno]/@desc1"/></B></td>
</tr>
<xsl:for-each select ='TBill'>
<xsl:choose>
<xsl:when test ='@self="Y"'>
<tr height ='22pt'>
<td></td>
<td align ='center'><xsl:if test ="accp/@code = 0 "><xsl:value-of select ="accp"/></xsl:if></td>
<td align='left' >&#160;<xsl:value-of select="station" /></td>
<td align='left' >&#160;<xsl:value-of select="invno" /></td>
<td align ='left'>&#160;<xsl:value-of select="concat(substring(dttran,9,2),'/',substring(dttran,6,2),'/',substring(dttran,3,2)+43)" />&#160;&#160;<xsl:value-of select="concat(substring(dttran,12,2),'.', substring(dttran,15,2))" /></td>
<td align='left' >&#160;<xsl:value-of select="rid" /></td>
<td align="center"><xsl:value-of select="HDflag" /></td>
<td align="center"><xsl:value-of select="paychk" /></td>
<td align ='right'><xsl:value-of select='format-number(hdrate,"#,##0.00")' />&#160;&#160;</td>
<td align ='right'>
<xsl:choose>
<xsl:when test="hdcharge>0"><xsl:value-of select='format-number(hdcharge,"#,##0.00")' /></xsl:when >
<xsl:otherwise test ="amount=0">-</xsl:otherwise >
</xsl:choose>
&#160;&#160;</td>
<td align = "right">
<xsl:choose>
<xsl:when test="amount>0">
	  <xsl:value-of select='format-number(amount,"#,##0.00")' />&#160;&#160;
</xsl:when >
<xsl:otherwise test ="amount=0">
	 - &#160;&#160;
</xsl:otherwise >
</xsl:choose>
</td>
<td align ='center'><xsl:value-of select="EPOstat" /></td>
<td align="center"><xsl:value-of select="cstat" /><xsl:value-of select="pstat" /></td>
</tr>

<xsl:if test ="adj/@code='1'">
<tr>
<td></td>
<td></td>
<td></td>
<td class ='adj' colspan ='4'><I><xsl:value-of select ="//config/AdjContent[@code='A']/@desc"/><xsl:value-of select ="adj/@id"/></I></td>
</tr>
</xsl:if>
</xsl:when>
<xsl:otherwise>
<xsl:comment>oth: Noshow</xsl:comment>
</xsl:otherwise>
</xsl:choose>
</xsl:for-each>
<tr height ='5pt'><td></td></tr>
<xsl:comment>End of TBill</xsl:comment>
</xsl:for-each>
<tr><td colspan = "13"><hr  size = '1px'  /></td></tr>

<xsl:comment>End of HDBill</xsl:comment>
</xsl:for-each>

<xsl:comment>sUmMarY : HD</xsl:comment>
<xsl:variable name ='sHDcasL'  select ="//STMdat[@code='HD']/dat[@code='L' and @type ='HD']/count"/>


<tr height ='25pt'>
<td></td>
<td class ='sum' colspan = '6'><B><xsl:value-of select="//config/SumContent[@code='L' and @id ='S']/@desc" /></B></td>
<td class ='' align ='right' colspan='4'><B><xsl:value-of select ='format-number($sHDcasL,"#,##0")'/>&#160;&#160;</B></td>
<td class ='sum'  colspan ='2'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='L' and @id ='S']/@unit" /></B></td>
</tr>
<tr><td colspan='14'><hr style="border: 1px solid #A7A7A7;" /></td></tr>


</tbody>
</table>
<xsl:call-template name="ShowRemarks">
	<xsl:with-param name="lstpath" select="3" />
</xsl:call-template>

</xsl:when>
<xsl:otherwise>
</xsl:otherwise>
</xsl:choose>
</p>

<p style ="margin-top :10pt">
<xsl:comment>EPO</xsl:comment>
<div class ='nonprint'>
 <br/>
 <br/>
 <br/>
</div> 
<xsl:choose>
<xsl:when test ="//STMdat[@code='HD']/dat[@code='E']/count &gt; 0">
<H3></H3>
<table BORDER = "0" width = "820" CELLSPACING = "0" cellpadding = "0">
<tr><td class ='sum'>
<font color ="#006600" size ='5pt'><B><xsl:value-of select="//config/Addheader[@code='E']/@desc" /></B></font>
</td></tr>
</table>
<table BORDER = "0" width = "820" CELLSPACING = "0" cellpadding = "0">
<thead>
<tr align ='center'>
<td width ='40'>&#160;</td>
<td width ='20'>&#160;</td>
<td width ='30'>&#160;</td>
<td width ='20'>&#160;</td>
<td width ='20'>&#160;</td>
<td width ='20'>&#160;</td>
<td width ='35'>&#160;</td>
<td width ='30'>&#160;</td>
<td width ='130'>&#160;</td>
<td width ='40'>&#160;</td>
<td width ='80'>&#160;</td>
<td width ='80'>&#160;</td>
<td width ='70'>&#160;</td>
<td width ='75'>&#160;</td>
<td width ='75'>&#160;</td>
<td width ='55'>&#160;</td>
</tr>
<tr height ='28pt' >
	<th style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF;border-left: 1px solid #2F97FF">ลำดับ</th>
	<th style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" colspan ='7' align ='left'>&#160;&#160;&#160;HN</th>
	<th style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  2px solid #2F97FF" colspan ='8' align ='left'>&#160;&#160;&#160;&#160;ชื่อ - สกุล</th>
</tr>

<tr height ='25pt' >
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden; border-left : 1px solid #2F97FF">&#160;</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" colspan='2'>Wkno</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" colspan='2'>HDs</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" colspan='2'>effHct</th>
	<th class ='snd' style="border-right: hidden; border-bottom: 2px solid #2F97FF; border-top : hidden" >&#160;</th>
	<th class ='snd' style="border-right: hidden; border-bottom: 2px solid #2F97FF; border-top : hidden" >&#160;</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" >&#160;</th>

	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" >Reimbs</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Quota</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" >&#160;</th>

	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" >PSess</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" >EpoPay</th>
	<th class ='snd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top :  hidden">EpoIs</th>
</tr>
<tr height ='28pt' >
	<th  class ='trd' style="border-right: hidden; border-bottom: 2px solid #2F97FF; border-top : hidden;border-left: 1px solid #2F97FF" >&#160;</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">&#160;</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Stat</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" colspan='3'>DTtran</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Hct</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Clm</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden" align='left'>&#160;&#160;&#160;&#160;Epotn</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Qty</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Reimb</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">MPrice</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">Pitem</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">PSes</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">&#160;</th>
	<th  class ='trd' style="border-right: 1px solid #2F97FF; border-bottom: 2px solid #2F97FF; border-top : hidden">EpoI</th>

</tr>
<tr><td colspan = "16"><hr  size = '1px'  /></td></tr>

</thead>
<tbody>

<xsl:for-each select="/STMSTM/HDBills/HDBill[generate-id(.) = generate-id(key('keyEHN', hn)[1])  ]">
<xsl:sort select="hn" order="ascending" /> 
<xsl:variable name="lngEHN"><xsl:value-of select="hn" /></xsl:variable>
<xsl:variable name="lstEHN" select="/STMSTM/HDBills/HDBill[hn=$lngEHN]" />


<xsl:variable name = 'PPpos'><xsl:value-of select="position()" /></xsl:variable>
<xsl:variable name = 'PPhn'><xsl:value-of select="hn" /></xsl:variable>
<xsl:variable name = 'PPname'><xsl:value-of select="name" /><xsl:if test ="benefit/@sub !=''">&#160;&#160;(<xsl:value-of select ="benefit/@main"/><xsl:value-of select ="benefit/@sub"/>)</xsl:if></xsl:variable>


<xsl:for-each select="$lstEHN[generate-id(.) = generate-id(key('keyEHNwk', concat(hn,wkno))[1]) and EPO/effHDs != 0 ]">
<xsl:sort select="wkno" order="ascending" />
<xsl:variable name ="ewkno" select ="wkno"/>
<xsl:choose>
<xsl:when test ="position() = 1">

<tr height ='28pt' bgcolor ='#99CCFF'>
<td align='right'><B><xsl:value-of select="$PPpos" /></B>&#160;</td>
<td colspan='7'>&#160;&#160;&#160;<B><xsl:value-of select="$PPhn" /></B></td>
<td colspan ='8'>&#160;&#160;&#160;&#160;<B><xsl:value-of select="$PPname" /></B></td>
</tr>
<tr><td colspan = "16"><hr  size = '1px'  /></td></tr>
</xsl:when>
<xsl:otherwise>
<xsl:comment>ไม่แสดง</xsl:comment>
</xsl:otherwise>
</xsl:choose>

<tr height = '22pt' >
<td></td>
<td align = 'left' bgcolor ='#B9DCFF' colspan='2'>&#160;&#160;<B><xsl:value-of select="$ewkno" /><xsl:value-of select="/STMSTM/config/Wkno/week[@code = $ewkno]/@desc1"/></B></td>
<td align = 'center' bgcolor ='#B9DCFF' colspan='2'><B><xsl:value-of select="hds" /></B></td>
<td align ='center' bgcolor ='#B9DCFF' colspan='2'><B><xsl:value-of select="EPO/effHCT" /></B></td>
<td bgcolor ='#B9DCFF' >&#160;</td>
<td bgcolor ='#B9DCFF' >&#160;</td>
<td bgcolor ='#B9DCFF' >&#160;</td>
<td align = 'Right' bgcolor ='#B9DCFF' ><B><xsl:value-of select='format-number(EPO/epoPay/@charge,"#,##0.00")' /></B>&#160;</td>
<td align = 'Right' bgcolor ='#B9DCFF' ><B><xsl:value-of select='format-number(EPO/epoPay/@limit,"#,##0.00")' /></B>&#160;</td>
<td bgcolor ='#B9DCFF' >&#160;</td>
<td align = 'Right' bgcolor ='#B9DCFF' ><B><xsl:value-of select='format-number(EPO/epoPay/@allow,"#,##0.00")' /></B>&#160;</td>
<td align = 'Right' bgcolor ='#B9DCFF' ><B><xsl:value-of select='format-number(EPO/epoPay,"#,##0.00")' /></B>&#160;</td>
<td align = 'Right' bgcolor ='#B9DCFF'><B><xsl:value-of select='format-number(EPO/epoAdm,"#,##0.00")' /></B>&#160;&#160;</td>
</tr>

<xsl:comment>Tbill</xsl:comment>
<xsl:for-each select ='TBill[accp/@code != 0 and cstat !="X" ]'>

<xsl:variable name ='Tdttran' select ="concat(substring(dttran,9,2),'/',substring(dttran,6,2),'/',substring(dttran,3,2)+43)"/>
<xsl:variable name ='Tself' select ="@self"/>
<xsl:variable name ='TcoHC' select ="hcode"/>
<xsl:variable name ='Tadj' select ="adj/@code"/>
<xsl:variable name ='Tadjid' select ="adj/@id"/>


<xsl:for-each select ="EPOs">
<xsl:variable name ='Estat' select ='stat'/>
<xsl:variable name ='EHCT' select ='HCT'/>
<xsl:variable name ='EPpays' select ='EPOpay/@pay'/>
<xsl:variable name ='EAllow' select ='EPOpay'/>
<xsl:variable name ='Eepoi' select ='EPOadm'/>


<xsl:choose>
<xsl:when test ="EPOcnt &gt; 0">

<xsl:for-each select ='EPO'>
<xsl:variable name = 'epoid'  select ='position()' />

<xsl:choose>
<xsl:when test ="$Tself='Y'">
<tr height ='20pt'>
<td></td>
<td></td>

<xsl:choose>
<xsl:when  test ='$epoid = 1'>
<td align='center'  ><xsl:value-of select ='$Estat'/></td>
<td colspan='3'   >&#160;<xsl:value-of select="$Tdttran" /></td>
<td align='center'  ><xsl:value-of select ='$EHCT'/></td>
</xsl:when>
<xsl:otherwise>
<td></td>
<td colspan='3' ></td>
<td></td>
</xsl:otherwise>
</xsl:choose>

<td align ='center'><xsl:value-of select ="epoclaim"/></td>
<td ><xsl:value-of select ='@eponame'/>&#160;<font size ='1pt'><xsl:value-of select = 'format-number(iu/@st,"###0")'/><xsl:value-of select ='iu/@unit'/></font></td>
<td align ='right' ><xsl:value-of select='format-number(iu/@qty,"##0.00")'/>&#160;</td>
<td align ='right' ><xsl:value-of select='format-number(epoPay/@charge,"#,##0.00")' />&#160;</td>
<td align ='right' ><xsl:value-of select='format-number(epoPay/@limit,"#,##0.00")' />&#160;</td>
<td align ='right' ><xsl:value-of select='format-number(epoPay,"#,##0.00")' />&#160;</td>

<xsl:choose>
<xsl:when  test ='$epoid = 1'>
<td align ='Right'   ><xsl:value-of select='format-number($EAllow,"#,##0.00")' />&#160;</td>
<td>&#160;</td>
<td align ='Right'   ><xsl:value-of select='format-number($Eepoi,"#,##0.00")' />&#160;&#160;</td>
</xsl:when>
<xsl:otherwise>
<td></td>
<td></td>
<td></td>
</xsl:otherwise>
</xsl:choose>
</tr>

</xsl:when>
<xsl:otherwise>
<xsl:comment>@self ='N'</xsl:comment>
<tr height ='23pt'>
<xsl:choose>
<xsl:when  test ='$epoid = 1'>
<td class ='oth' colspan='2' align ='right'><I>[<xsl:value-of select='$TcoHC'/>]</I></td>
<td class ='oth' align='center' ><I><xsl:value-of select ='$Estat'/></I></td>
<td class ='oth' colspan='3'  >&#160;<I><xsl:value-of select="$Tdttran" /></I></td>
<td class ='oth' align='center'  ><I><xsl:value-of select ='$EHCT'/></I></td>
</xsl:when>
<xsl:otherwise>
<td class ='oth'></td>
<td class ='oth' colspan='5' ></td>
<td class ='oth'></td>
</xsl:otherwise>
</xsl:choose>

<td class ='oth' align ='center'><I><xsl:value-of select ="epoclaim"/></I></td>
<td class ='oth' ><I><xsl:value-of select ='@eponame'/>&#160;<font size ='1pt'><xsl:value-of select = 'format-number(iu/@st,"###0")'/><xsl:value-of select ='iu/@unit'/></font></I></td>
<td class ='oth' align ='right' ><I><xsl:value-of select='format-number(iu/@qty,"##0.00")'/></I>&#160;</td>
<td class ='oth' align ='right' ><I><xsl:value-of select='format-number(epoPay/@charge,"#,##0.00")' /></I>&#160;</td>
<td class ='oth' align ='right' ><I><xsl:value-of select='format-number(epoPay/@limit,"#,##0.00")' /></I>&#160;</td>
<td class ='oth' align ='right' ><I><xsl:value-of select='format-number(epoPay,"#,##0.00")' /></I>&#160;</td>
<xsl:choose>
<xsl:when  test ='$epoid = 1'>
<td class ='oth' align ='Right'   ><I><xsl:value-of select='format-number($EPpays,"#,##0.00")' /></I>&#160;&#160;</td>
<td class ='oth' >&#160;</td>
<td class ='oth' align ='Right'   ><I><xsl:value-of select='format-number($Eepoi,"#,##0.00")' /></I>&#160;&#160;</td>
</xsl:when>
<xsl:otherwise>
<td class ='oth' ></td>
<td class ='oth' ></td>
</xsl:otherwise>
</xsl:choose>
</tr>

</xsl:otherwise>
</xsl:choose>

<xsl:if test ="split/@code ='1' ">
<tr>
<td colspan ='8'></td>
<td class ='spl'  colspan ='6' >
<I>
<xsl:choose>
<xsl:when  test ="split/@recno = 1">
<xsl:value-of select ="//config/SplContent[@code= 'I' and @id ='F']/@desc"/>
<xsl:value-of select='split/@recs'/>
<xsl:value-of select ="//config/SplContent[@code= 'I' and @id ='F']/@unit"/>
<xsl:value-of select ="//config/SplContent[@code= 'I' and @id ='F']/@desc1"/>
</xsl:when>
<xsl:otherwise>
<xsl:value-of select ="//config/SplContent[@code= 'I' and @id ='M']/@desc"/>
<xsl:value-of select='split/@wkno'/>
<xsl:value-of select ="//config/SplContent[@code= 'I' and @id ='M']/@unit"/>
<xsl:value-of select ="//config/SplContent[@code= 'I' and @id ='M']/@desc1"/>
</xsl:otherwise>
</xsl:choose>
<xsl:value-of select='split/@recno'/>/<xsl:value-of select='split/@recs'/>
</I></td>
</tr>
</xsl:if>

</xsl:for-each><!--EPO-->

<xsl:if test ="$Tadj ='1' ">
<tr>
<td></td>
<td></td>
<td></td>
<td class ='adj'  colspan ='6' ><I><xsl:value-of select ="//config/AdjContent[@code='A']/@desc"/><xsl:value-of select ="$Tadjid"/></I></td>
</tr>
</xsl:if>

</xsl:when>
<xsl:otherwise>

</xsl:otherwise>
</xsl:choose><!--EPOcnt-->
</xsl:for-each><!--EPOS-->
</xsl:for-each><!--TBill-->


<tr height ='2pt'>
<td></td>
</tr>
</xsl:for-each>
<!-->
<tr><td colspan = "16"><hr  size = '1px'  /></td></tr>
<!-->
</xsl:for-each>

<xsl:comment>sUmMarY : EPO</xsl:comment>
<xsl:variable name ='sEPOPAY'  select ="//STMdat[@code='HD']/dat[@code='E' and @type ='EPO']/payable[@code='E' and @type ='EPOPAY']"/>
<xsl:variable name ='sEPOADM'   select ="//STMdat[@code='HD']/dat[@code='E' and @type ='EPO']/payable[@code='E' and @type ='EPOADM']"/>
<xsl:variable name ='sEPOAMT'   select ="//STMdat[@code='HD']/dat[@code='E' and @type ='EPO']/total"/>

<tr><td colspan = "16"><hr  size = '1px'  /></td></tr>
<tr height ='25pt'>
<td></td>
<td class ='sum' colspan = '11'><B><xsl:value-of select="//config/SumContent[@code='E' and @id ='B']/@desc" /></B></td>
<td class ='' align ='right' colspan='3'><B><xsl:value-of select ='format-number($sEPOPAY,"#,##0.00")'/>&#160;&#160;</B></td>
<td class ='sum'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='E' and @id ='B']/@unit" /></B></td>
</tr>
<tr height ='25pt'>
<td></td>
<td class ='sum' colspan = '11'><B><xsl:value-of select="//config/SumContent[@code='E' and @id ='A']/@desc" /></B></td>
<td class ='' align ='right' colspan='3'><B><xsl:value-of select ='format-number($sEPOADM,"#,##0.00")'/>&#160;&#160;</B></td>
<td class ='sum'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='E' and @id ='A']/@unit" /></B></td>
</tr>
<tr height ='25pt'>
<td></td>
<td class ='sum' colspan = '11'><B><xsl:value-of select="//config/SumContent[@code='E' and @id ='S']/@desc" /></B></td>
<td class ='' align ='right' colspan='3'><B><xsl:value-of select ='format-number($sEPOAMT,"#,##0.00")'/>&#160;&#160;</B></td>
<td class ='sum'>&#160;&#160;<B><xsl:value-of select="//config/SumContent[@code='E' and @id ='S']/@unit" /></B></td>
</tr>
<tr><td colspan='16'><hr style="border: 1px solid #A7A7A7;" /></td></tr>
</tbody>
</table>

<xsl:call-template name="ShowRemarks">
	<xsl:with-param name="lstpath" select="2" />
</xsl:call-template>


</xsl:when>
<xsl:otherwise>

</xsl:otherwise>
</xsl:choose>


<xsl:comment>end of EPO</xsl:comment>	
</p>
</body>
</html>
</xsl:template>




<xsl:template name ="ShowHDEPO">
<H5>
<a name="ShowEPO" id="001"></a>
<table BORDER = "0" width = "700" CELLSPACING = "0" cellpadding = "0">
<thead>
<tr height ='25pt' >
	<td width ="50">&#160;</td>
	<td width ="110">&#160;</td>
	<td width ="60">&#160;</td>
	<td width ="30">&#160;</td>
	<td width ="60">&#160;</td>
	<td width ="60">&#160;</td>
	<td width = "90">&#160;</td>
	<td width = "90">&#160;</td>
	<td width = "90">&#160;</td>
	<td width = "60">&#160;</td>
</tr>
<tr>	<td   CLASS = "sum" colspan = "10"><font color ="#993333" size ='5pt'><B><xsl:value-of select="//config/Addheader/@desc" /></B></font></td></tr>
<tr height ='25pt' >
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top :  1px solid">ลำดับ</th>
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top :  1px solid" >HN</th>
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top :  1px solid" colspan ='8' align ='left'>&#160;&#160;&#160;&#160;ชื่อ - สุกล</th>
</tr>
<tr height ='25pt' >
	<th rowspan='2' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden"></th>
	<th rowspan='2' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden"></th>
	<th rowspan='2' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">Wkno</th>
	<th rowspan='2' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">ST</th>
	<th rowspan='2' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden" >HDs</th>
	<th rowspan ='2' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">effHCT</th>
	<th colspan='3' style="border-right: 1px solid; border-bottom: 1px solid; border-top :  hidden">ค่ายา EPOETIN</th>
	<th style="border-right:  1px solid; border-bottom: hidden; border-top :  hidden">Epo</th>
</tr>
<tr height ='22pt' valign ='top'>
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">เพดาน</th>
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">เรียกเก็บ</th>
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">เบิกได้</th>
	<th style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">ADM</th>
</tr>
<tr height ='25pt' >
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden" >&#160;</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">flg</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden" colspan ='2' align ='center'>&#160;วันที่ใช้บริการ&#160;</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">HCT</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">EPOiu</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">Reimb</th>
	<th  class ='snd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>

</tr>
<xsl:comment>แต่ละ item</xsl:comment>
<tr height ='25pt'>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">EPOTN</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">EPOiu</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">Epoclaim</th>
	<th class ='trd' style="border-right: 1px solid; border-bottom: 1px solid; border-top : hidden">&#160;</th>
</tr>
<tr><td colspan = "10"><hr  size = '1px'  /></td></tr>

</thead>
<tbody>

<xsl:for-each select="/STMSTM/HDBills/HDBill[generate-id(.) = generate-id(key('keyEHDwk', hn)[1])]">
<xsl:sort select="hn" order="ascending" /> 
<xsl:variable name="lngEhdstation"><xsl:value-of select="hn" /></xsl:variable>
<xsl:variable name="lstEhdstation" select="/STMSTM/HDBills/HDBill[hn=$lngEhdstation]" />
<tr height = '25pt' bgcolor ='#99CCFF'>
<td >&#160;&#160;<B><xsl:value-of select="position()" /></B></td>
<td >&#160;&#160;<B><xsl:value-of select="hn" /></B></td>
<td colspan ='8'>&#160;&#160;&#160;&#160;<B><xsl:value-of select="name" /></B></td>
</tr>
<tr><td colspan = "10"><hr  size = '1px'  /></td></tr>

 <xsl:for-each select="$lstEhdstation[generate-id(.) = generate-id(key('keyEHDwksess', concat(hn,wkno))[1])]">
 <xsl:sort select="wkno" order="ascending" />
<tr height = '22pt'>
<td></td>
<td></td>
<td align = 'left' >&#160;&#160;<B><xsl:value-of select="wkno" /></B></td>
<td align = 'left' >&#160;&#160;<B><xsl:value-of select="EPO/stat" /></B></td>
<td align = 'center' ><B><xsl:value-of select="hds" /></B></td>
<td align ='center' ><B><xsl:value-of select="EPO/effHCT" /></B></td>
<td align = 'Right' ><B><xsl:value-of select='format-number(EPO/epoPay/@limit,"#,##0")' /></B>&#160;&#160;</td>
<td align = 'Right' ><B><xsl:value-of select='format-number(EPO/epoPay/@charge,"#,##0")' /></B>&#160;&#160;</td>
<td align = 'Right' ><B><xsl:value-of select='format-number(EPO/epoPay,"#,##0")' /></B>&#160;&#160;</td>
<td align = 'Right' ><B><xsl:value-of select='format-number(EPO/epoAdm,"#,##0")' /></B>&#160;&#160;</td>
</tr>
<tr>
<td></td>
<td></td>
<td colspan = "8"><hr  size = '1px'  /></td>
</tr>

<xsl:comment>Detail</xsl:comment>
<xsl:for-each select ="TBill">
<tr height ='20pt'>
<td></td>
<td></td>
<td></td>
<td bgcolor ='#CEE7FF' align ='center'>&#160;<xsl:value-of select="EPOs/stat" />&#160;</td>
<td bgcolor ='#CEE7FF' colspan='2' align ='left'>&#160;&#160;&#160;&#160;<xsl:value-of select="concat(substring(dttran,9,2),'/',substring(dttran,6,2),'/',substring(dttran,3,2)+43)" />&#160;&#160;<xsl:value-of select="concat(substring(dttran,12,2),'.', substring(dttran,15,2))" /></td>
<td bgcolor ='#CEE7FF' align ='center'><xsl:value-of select="EPOs/HCT" /></td>
<td bgcolor ='#CEE7FF' align = 'Right'><xsl:value-of select='format-number(EPOs/EPOiu,"#,##0")' />&#160;&#160;&#160;&#160;</td>
<td bgcolor ='#CEE7FF' align = 'Right'><xsl:value-of select="EPOs/EPOcharge" />&#160;&#160;&#160;&#160;</td>
<td bgcolor ='#CEE7FF' align ='Right'>&#160;</td>
</tr>
<xsl:variable name ='ecnt' select ="EPOs/EPOcnt"/>
<xsl:choose>
<xsl:when test ='$ecnt &gt; 0 '>
<xsl:for-each select ="EPOs/EPO">
<tr>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td align ='left'><I>&#160;<xsl:value-of select="@eponame" /></I></td>
<td align ='right'><I><xsl:value-of select='format-number(iu,"#,##0")' /></I>&#160;&#160;&#160;&#160;</td>
<td align ='right'><I><xsl:value-of select="epoclaim" /></I>&#160;&#160;&#160;&#160;</td>
<td align ='center'><I>&#160;<xsl:value-of select="stat" /></I></td>
</tr>
</xsl:for-each>
</xsl:when>
<xsl:otherwise>
<xsl:comment>ไม่มี items</xsl:comment>
</xsl:otherwise>
</xsl:choose>
</xsl:for-each>
<tr>
<td></td>
<td></td>
<td colspan = "8"><hr  size = '1px'  /></td>
</tr>
</xsl:for-each>
<tr>
<td colspan='10'><hr/></td>
</tr>
</xsl:for-each>
<tr height ='22pt' >
<td colspan='2'><B>สรปค่ายา EPOETIN</B></td>
<td colspan ='5'></td>
<td colspan='3' align ='right'><B><xsl:value-of select='format-number(sum(//HDBill/EPO/epoPay),"#,##0")' /></B>&#160;&#160;&#160;&#160;</td>
</tr>
<tr height ='22pt' >
<td colspan='2'><B>สรุปค่าฉีดยา EPOETIN</B></td>
<td colspan ='5'></td>
<td colspan='3' align ='right'><B><xsl:value-of select='format-number(sum(//HDBill/EPO/epoAdm),"#,##0")' /></B>&#160;&#160;&#160;&#160;</td>
</tr>
<tr height ='22pt' >
<td colspan='2'><B>รวมทั้งสิ้น</B></td>
<td colspan ='5'></td>
<td colspan='3' align ='right'><B><xsl:value-of select='format-number(sum(//HDBill/EPO/epoPay)+sum(//HDBill/EPO/epoAdm),"#,##0")' /></B>&#160;&#160;&#160;&#160;</td>
</tr>

<tr>
<td colspan='10'><hr size ='3pt'/></td>
</tr>
</tbody>
</table>
</H5>
</xsl:template>

<xsl:template name="ShowHDEPOo">
<H5>
<a name="ShowEPO" id="001"></a>
<table BORDER = "0" width = "720" CELLSPACING = "0" cellpadding = "0">
<thead>
<tr>	<td   CLASS = "sum" colspan = "12"><font color ="#993333" size ='5pt'><B><xsl:value-of select="//config/Addheader[@code='E']/@desc" /></B></font></td></tr>
<tr>
	<td width ="45">&#160;</td>
	<td width ="90">&#160;</td>
	<td width ="40">&#160;</td>
	<td width = "30">&#160;</td>
	<td width ="110">&#160;</td>
	<td width = "100">&#160;</td>
	<td width = "60">&#160;</td>
	<td width = "55">&#160;</td>
	<td width = "60">&#160;</td>
	<td width = "60">&#160;</td>
	<td width = "70">&#160;</td>
</tr>
<tr><td colspan ='3'><font color = '#336633' ><B><xsl:value-of select="//AddBills[@code='EPO']/@desc1" /></B></font></td><td colspan ='9'><font color = '#336633' >&#160;&#160;<xsl:value-of select="//config/Epoheader[@code='EPO']/@desc" /></font></td></tr>

<xsl:variable name="sc" select="count(//config/Epoheader[@code='STM'])"/>	 
<xsl:choose>		
	<xsl:when test="$sc = 0">
	</xsl:when>	
	<xsl:otherwise>
	<tr>
	<td colspan='3'></td>
	<td  colspan ='9'> <font color = '#336633' >&#160;&#160;&#160;
		<xsl:value-of select="//config/Epoheader[@code='STM']/@desc" />
		<xsl:for-each select ="//config/Epoheader[@code='STM']/stmid">
			<xsl:value-of select ="@code"/> = <xsl:value-of select ="@id"/>
		</xsl:for-each>
	</font>
	</td>
	</tr>
</xsl:otherwise>
</xsl:choose>	
<tr >
      <th style="border-right: 1px solid; border-left: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom" >รายการ</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">HN</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">WkNo</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">HDs</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">RepHct</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">RepEPO</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">เรียกเก็บ</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">eff Hct</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">EPO Unit</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid" align="center" valign ="bottom">EPO Pay</th>
      <th style="border-right: 1px solid; border-bottom:hidden; border-top :  1px solid"  align="center" valign ="bottom" >STM No.</th>
</tr>

<tr><td colspan = "14"><hr  size = '1pt'  /></td></tr>
</thead>
<tbody>
<xsl:for-each select ="//AddBills[@code='EPO']/TBill">
<tr>
	<td align ="Left" valign ="top" ><xsl:value-of select="position()" /></td>
	<td valign ="top" ><xsl:value-of select ="hn"/></td>	
	<td align ="Center" valign ="top" ><xsl:value-of select ="wkno"/></td>
	<td align ="Center" valign ="top" ><xsl:value-of select ="HDcnt"/></td>
	<td align ="left" valign ="top" >&#160;<xsl:value-of select="hHCT" /></td>
	<td align ="left" valign ="top" >&#160;<xsl:value-of select="hEPOIU" /></td>
	<td align ="right" valign ="top" ><xsl:value-of select='format-number(hReimb,"###0")' /></td>
	<td align ="Center" valign ="top" >&#160;<xsl:value-of select="pHCT" /></td>
	<td align ="right" valign ="top" >&#160;<xsl:value-of select='format-number(pEPOIU,"###0")' /></td>
	<td align ="right" valign ="top" ><xsl:value-of select='format-number(pReimb,"###0")' /></td>
	<td align ="Center" valign ="top" ><xsl:value-of select ="STMID"/></td>
</tr>
	<xsl:choose>
		<xsl:when test="cowatch =1">
			<xsl:for-each select ="HDs/HD">
			<tr>
			<td CLASS = "EPO"  align ="Left" >&#160;</td>
			<td CLASS = "EPO"   align ='left' >[<xsl:value-of select ="hcode"/>]</td>	
			<td CLASS = "EPO"   align ="Center" ><xsl:value-of select ="wkno"/></td>
			<td CLASS = "EPO"   align ="Center" ><xsl:value-of select ="HDcnt"/></td>
			<td CLASS = "EPO"   align ="left" >&#160;<xsl:value-of select="hHCT" /></td>
			<td CLASS = "EPO"   align ="left" >&#160;<xsl:value-of select="hEPOIU" /></td>
			<td CLASS = "EPO"   align ="right" ><xsl:value-of select='format-number(hReimb,"###0")' /></td>
			<td CLASS = "EPO"   align ="Center" >&#160;<xsl:value-of select="pHCT" /></td>
			<td CLASS = "EPO"   align ="right" >&#160;<xsl:value-of select='format-number(pEPOIU,"###0")' /></td>
			<td CLASS = "EPO"   align ="right" ><xsl:value-of select='format-number(pReimb,"###0")' /></td>
			<td CLASS = "EPO"   align ="Center" ><xsl:value-of select ="STMID"/></td>
			</tr>
			</xsl:for-each>
		</xsl:when>
		<xsl:otherwise>
		</xsl:otherwise>
	</xsl:choose>
</xsl:for-each>
<xsl:for-each select ="//AddBills[@code='EPO']">
<tr><td colspan = "14"><hr  size = '1pt'  /></td></tr>
<tr>
<td colspan ="2"><B>รวมเบิกเพิ่ม</B></td>
<td  align ='right'><B><xsl:value-of select='format-number(count(TBill[cstat !="H"]),"#,##0")' />&#160;&#160;&#160;รายการ&#160;</B></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td align ="Right" colspan ="2"><B><xsl:value-of select='format-number(sum(TBill[cstat !="H"]/pReimb),"#,##0")' /></B></td>
</tr>
<tr><td colspan = "14"><hr  size = '1pt'  /></td></tr>
</xsl:for-each>
</tbody>
</table>
 </H5>
</xsl:template>

<xsl:template name="ShowRemarks">
<xsl:param name="lstpath" />

    <table  width = "700" >
	<tr>
	<td width ='100'>&#160;</td>
	<td width ='600'>&#160;</td>
	</tr>
	<xsl:for-each select ="//STMSTM/Remarks">
	<tr >
	<td colspan='2'><font size ='1'><xsl:value-of select= "@desc"/></font> </td>
	</tr>
	<xsl:for-each select ="item[@pathno=$lstpath]">
	<tr valign ='top' >
	<xsl:choose>
	<xsl:when test ="@type ='H'">
	<td colspan = '2'>&#160;&#160;&#160;&#160;<font size ='1'><B><xsl:value-of select= "@code"/></B></font></td>
	</xsl:when>
	<xsl:when test ="@type ='T'">
	<td align ='right'><font size ='1'><xsl:value-of select= "@code"/>&#160;</font></td>
	<td><font size ='1'><xsl:value-of select= "@desc"/></font></td>
	</xsl:when>
	<xsl:otherwise>
	<td align ='right'><font size ='1'><B><xsl:value-of select= "@code"/></B>&#160;</font></td>
	<td><font size ='1'><xsl:value-of select= "@desc"/></font></td>
	</xsl:otherwise>
	</xsl:choose>
	</tr>
	</xsl:for-each>
	</xsl:for-each>  
	</table>
</xsl:template>
</xsl:stylesheet> 