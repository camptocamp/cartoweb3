      <!-- layerReorder -->

      <complexType name="LayerReorderInit">
        <all>
          <element name="layers" type="types:ArrayOfLayerInit"/>
        </all>
      </complexType>

      <complexType name="ArrayOfLayerInit">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LayerInit[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="LayerInit">
        <all>
          <element name="id" type="xsd:string"/>
          <element name="label" type="xsd:string"/>
          <element name="transparency" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="ArrayOfLayerTransparency">
        <complexContent>
          <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LayerTransparency[]"/>
          </restriction>
        </complexContent>
      </complexType>

      <complexType name="LayerTransparency">
        <all>
          <element name="id" type="xsd:string"/>
          <element name="transparency" type="xsd:string"/>
        </all>
      </complexType>

      <complexType name="LayerReorderRequest">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="layerIds" type="types:ArrayOfString"/>
          <element name="layerTransparencies" type="types:ArrayOfLayerTransparency"/>
        </all>
      </complexType>

      <complexType name="LayerReorderResult"/>
