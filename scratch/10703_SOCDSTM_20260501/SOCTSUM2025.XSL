<?xml version="1.0" encoding="Windows-874"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"   
xmlns:msxsl="urn:schemas-microsoft-com:xslt" 
xmlns:user="script:user"
xmlns:fo="http://www.w3.org/1999/XSL/Format">
<xsl:template match="/"> 
<xsl:comment>Last Update : 15-12-2016</xsl:comment>
  <html>
  <head>
			<style>
				body {margin-top: 5pt; margin-left: 15pt; margin-right: 10pt; margin-top: 10pt }
				p{font-family:Angsana New; font-size:14pt; align: left; margin-top: -12pt}
				td{font-family:Angsana New; font-size:14pt; align: left; margin-top: -18pt; height: 12pt}
			</style>		

			<Style Type = "text/css" MEDIA = "screen">
				.ticketid{
				display:none;
				}
			</Style>
			<Style Type = "text/css" MEDIA = "print">
				.btprint{
				display:none;
				}
			</Style>
       <title><xsl:value-of select="//STMSUMM/stmno"/></title>	

   </head>
   <body >
   <p style="margin-left: 10pt">
   <table border = "0" width = "610" >
      <xsl:for-each select="STMSUMM/chi">
   <tr>
   <td height = "10"  width = "75" align ="center" rowspan = '3' > 
		                      <xsl:choose>
				      <xsl:when test = "image[.!='']">
		                      <xsl:element name = "IMG">
				      <xsl:attribute name = "SRC">
				      <xsl:value-of select="image"/>
				      </xsl:attribute>
				      </xsl:element>
    	                               </xsl:when>
				       <xsl:otherwise>
					n/a
		                       </xsl:otherwise>
				       </xsl:choose>
   </td>
   <td width = "10"></td>
   <td ><B>สำนักสารสนเทศบริการสุขภาพ (สกส.)</B></td>
   </tr>
   <tr>
    <td></td>
   <td ><font  size ="3pt">979/103-104 ชั้น 31 อาคารเอสเอ็มทาวน์เวอร์ ถ.พหลโยธิน  สามเสนใน  พญาไท  กรุงเทพฯ</font></td>
   </tr>
    <tr>
   <td></td>
   <td ><font  size ="3pt">โทร : 0-2298-0405-8  &#160;&#160;&#160;โทรสาร : 0-2298-0409 &#160;&#160;&#160; URL: http://www.chi.or.th</font></td>
   </tr>

   </xsl:for-each>
   </table>
   </p>

	<p style="margin-left: 15pt; margin-top: 10pt">
	<table border = "0"  width="610"  cellspacing = '0'  cellpadding = '0'> 
	<tr>
		<td  width ='85'>ที่</td>
		<td  colspan ='2'><xsl:value-of select="//STMSUMM/stmno"/></td>
	</tr>
	<tr>
		<td width ='85'></td>
		<td width ='270'></td>
		<td width ='255'>วันที่  &#160;&#160;<xsl:value-of select="//STMSUMM/dateIssue"/></td>
	</tr>
	<tr>
		<td  width ='85'>เรื่อง</td>
		<td  colspan ='2'><xsl:value-of select="//config/STMheader/@desc"/></td>
	</tr>
 <xsl:variable name="Spd" select="//spacial/desc"/>	 <!--select="amount"  -->
 <xsl:choose>		
	<xsl:when test="$Spd = ''">
	</xsl:when>		
	<xsl:otherwise>
	<tr>
		<td  width ='85'></td>
		<td  colspan ='2' style="margin-top: -14pt; color: red;" ><xsl:value-of select="//spacial/desc"/></td>
	</tr>
	</xsl:otherwise>
 </xsl:choose>		

	<tr>
		<td  width ='85'>เรียน</td>
		<td  colspan ='2'>ผู้อำนวยการ<xsl:value-of select="//STMSUMM/hname"/> &#160;(<xsl:value-of select="//STMSUMM/hcode"/><xsl:for-each select = "//STMSUMM/hcode/@id">&#160;:&#160;<xsl:value-of select ="."/></xsl:for-each>)</td>
	</tr>
	<xsl:variable name ='chd'  select ="//STMSUMM/atth/@desc" />
	<xsl:for-each select ='//STMSUMM/atth/doc'>
			<tr>
					<td  width ='85'><xsl:if test = "position() =1 "><xsl:value-of select='$chd'/></xsl:if></td>
					<td colspan ='2'><xsl:value-of select="@desc"/>&#160;<xsl:value-of select="."/></td>
				</tr>
	</xsl:for-each>
	</table>

	</p>
	<p style="margin-left: 15pt; margin-top:-10pt">
	<table   border = "0"  width="610" cellspacing = '0'  cellpadding = '0'> 
	<tr>
		<td width ='85'></td>
		<td width ='190'></td>
		<td width ='100'></td>
		<td width ='40'></td>
		<td width ='145'></td>
		<td width = '15'></td>
		<td width = '35' ></td>
	</tr>
	<tr>
		<td  width ='85'></td>
		<td colspan = '6' align = 'left'>สำนักสารสนเทศบริการสุขภาพ&#160;&#160;&#160;&#160;ขอส่งบัญชีรายการเบิกผู้ป่วยระบบประกันสังคม</td>
	</tr>
	<tr>
		<td colspan = '7'>ประเภทผู้ป่วยนอกโรคไตวายเรื้อรัง&#160;&#160;ของสถานพยาบาลท่าน&#160;&#160;ตั้งแต่วันที่&#160;<xsl:value-of select="//STMSUMM/dateStart"/>&#160;ถึงวันที่&#160;&#160;<xsl:value-of select="//STMSUMM/dateEnd"/></td>
	</tr>
	<tr>
		<td colspan = '7'>เลขที่เอกสาร&#160;&#160;<xsl:value-of select="//STMSUMM/atth/doc[@id='1']"/>&#160;&#160;โดยสรุปเป็นค่าใช้จ่ายประเภทต่างๆ และยอดรวมดังนี้</td>
	</tr>
	<tr>
		<td></td>
		<td colspan ='6'><HR/></td>
	</tr>

	<xsl:for-each select="//STMSUMM/STMdat">
	<xsl:variable name='scode' select ="@code"/>

	<tr>
	<td></td>
	<td colspan = '4'><B>สำหรับ<xsl:value-of select ='@name'/>&#160;-&#160;<xsl:value-of select ="@desc1"/></B></td>
	</tr>
	<xsl:for-each select ="dat">
	<xsl:variable name='dcode' select ="@code"/>
	<xsl:variable name ='stotal' select ="total"/>
	<tr valign ='middle'>
	<td></td>
	<td><xsl:value-of select ="//config/SumContent[@code=$scode and @id =$dcode and @type='A']/@desc "/></td>
	<td align ='right' ><B><xsl:value-of select='format-number(count,"#,##0")'/></B></td>
	<td >&#160;&#160;ราย</td>
	<xsl:choose>
	<xsl:when test ='$dcode="E" or $dcode="P" '>
	<td  align ='right' ><xsl:value-of select= 'format-number($stotal, "#,##0.00")'/></td>
	<td ></td>
	<td  > บาท</td>
	</xsl:when>
	<xsl:otherwise>
	</xsl:otherwise>
	</xsl:choose>
	</tr>
	<xsl:for-each select ="paycount">
	<xsl:variable name ='pctype' select ='@type'/>
	<tr  valign ='middle'>
	<td></td>
	<td>&#160;&#160;&#160;&#160;<xsl:value-of select ="//config/SumContent[@code=$scode and @id =$dcode and @type= $pctype]/@desc "/></td>
	<td align ='right' ><xsl:value-of select='format-number(.,"#,##0")'/></td>
	<td >&#160;&#160;ราย</td>
	<xsl:choose>
	<xsl:when test='$pctype="S"'>
	<td  align ='right' ><xsl:value-of select= 'format-number($stotal, "#,##0.00")'/></td>
	<td ></td>
	<td  > บาท</td>
	</xsl:when>
	<xsl:otherwise>
	<td></td>
	<td></td>
	<td></td>
	</xsl:otherwise>
	</xsl:choose>
	</tr>
	</xsl:for-each>


	</xsl:for-each>
	<tr>
	<td></td>
	<td colspan ='6'><HR/></td>
	</tr>

	</xsl:for-each>

	<xsl:comment>
	<tr>
		<td></td>
		<td><B>จำนวนรายการทั้งสิ้น</B></td>
		<td></td>
		<td></td>
		<td align ='right'><B><xsl:value-of select= 'format-number(//STMSUMM/acount, "#,##0")'/></B></td>
		<td></td>
		<td><B>ราย</B></td>
	</tr>
	</xsl:comment>
	<tr>
		<td></td>
		<td><B>รวมยอดเบิกทั้งสิ้น</B></td>
		<td></td>
		<td></td>
		<td align ='right'><B><xsl:value-of select= 'format-number(//STMSUMM/amount, "#,##0.00")'/></B></td>
		<td></td>
		<td><B>บาท</B></td>
	</tr>
	<tr>
		<td></td>
		<td colspan='6' align = 'right'><B>(<xsl:value-of select="//STMSUMM/thamount"/>)</B></td>
	</tr>
	<tr>
		<td></td>
		<td colspan ='6'><HR/></td>
	</tr>
	<tr>
		<td></td>
		<td  colspan ='6'>รายละเอียดการเบิกตามแต่ละรายการ&#160;&#160;แสดงไว้ในเอกสารอิเล็กทรอนิกส์ที่แนบมาด้วย&#160;&#160;หากท่านตรวจสอบ</td>
	</tr>
	<tr>
		<td  colspan ='7'>ความถูกต้องแล้ว&#160;&#160;ขอได้โปรดดำเนินการเบิกตามขั้นตอนที่กำหนดไว้ในแนวทางปฏิบัติ</td>
	</tr>
	<!-->
	<tr>
		<td></td>
		<td  colspan ='6'>หากพบข้อมูลผิดพลาด&#160;&#160;โปรดแจ้ง สกส.  ภายในวันที่ &#160;&#160; <xsl:value-of select="//STMSUMM/datedue"/></td>
	</tr>
	<!-->

	</table>
	</p>
	<p style="margin-left: 15pt; margin-top:2pt">
	<table   border = "0"  width="610" > 
	<tr>
		<td width = '260'></td>
		<td width = '380' align ='center'>ขอแสดงความนับถือ</td>
		<td width = '70'></td>
	</tr>
	<tr>
		<td ></td>
		<td  align ='center'><br></br>&#160;นายสุเมธี&#160;&#160;&#160;เชยประเสริฐ</td>
		<td ></td>
	</tr>
	<tr>
		<td ></td>
		<td  align ='center'>&#160;ผู้จัดการสำนักสารสนเทศบริการสุขภาพ</td>
		<td ></td>
	</tr>
	</table>
	</p>
	<p style="margin-left: 15pt">
		<DIV class = "Ticketid">
			<B><xsl:value-of select="STMSUMM/ticket/id"/></B>
		</DIV>
	</p>
   </body>
   </html>
</xsl:template>


</xsl:stylesheet> 