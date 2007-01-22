      <!-- tables -->

      <complexType name="TableFlags">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="returnAttributes" type="xsd:boolean"/>
          <element name="returnTable" type="xsd:boolean"/>
        </all>
      </complexType>

      <complexType name="ArrayOfTableGroup">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:TableGroup[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="TableGroup">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="groupId" type="xsd:string"/>
          <element name="groupTitle" type="xsd:string"/>
          <element name="tables" type="types:ArrayOfTable"/>
        </all>
      </complexType>

      <complexType name="ArrayOfTable">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:Table[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="Table">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="tableId" type="xsd:string"/>
          <element name="tableTitle" type="xsd:string"/>
          <element name="numRows" type="xsd:integer"/>
          <element name="totalRows" type="xsd:integer"/>
          <element name="offset" type="xsd:integer"/>
          <element name="totalPages" type="xsd:integer"/>
          <element name="page" type="xsd:integer"/>
          <element name="rowsPage" type="xsd:integer"/>
          <element name="columnIds" type="types:ArrayOfString"/>
          <element name="columnTitles" type="types:ArrayOfString"/>
          <element name="noRowId" type="xsd:boolean"/>
          <element name="rows" type="types:ArrayOfTableRow"/>
        </all>
      </complexType>

      <complexType name="ArrayOfTableRow">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:TableRow[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="TableRow">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="rowId" type="xsd:string"/>
          <element name="cells" type="types:ArrayOfString"/>
        </all>
      </complexType>
