      <!-- query -->

      <complexType name="QueryRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="bbox" type="types:Bbox"/>
          <element name="layerIds" type="types:ArrayOfLayerId"/>
        </all>
      </complexType>

      <complexType name="QueryResult">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layerResults" type="types:ArrayOfLayerResult"/>
        </all>
      </complexType>

      <complexType name="ArrayOfLayerResult">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LayerResult[]"/>
          </restriction>
        </complexContent>
      </complexType>
      
      <complexType name="LayerResult">
        <all>
          <element name="layerId" type="xsd:string"/>
          <element name="numResults" type="xsd:integer"/>
          <element name="fields" type="types:ArrayOfString"/>
          <element name="resultElements" type="types:ArrayOfResultElement"/>
        </all>
      </complexType>

      <complexType name="ArrayOfResultElement">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:ResultElement[]"/>
          </restriction>
        </complexContent>
      </complexType>

      
      <complexType name="ResultElement">
        <all>
          <element name="id" type="xsd:string"/>
          <element name="values" type="types:ArrayOfString"/>
        </all>
      </complexType>
