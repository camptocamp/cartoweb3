      <!-- mapOverlay -->
      
      <complexType name="PositionOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="type" type="xsd:int"/>
          <element name="index" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
        </all>
      </complexType>
      
      <complexType name="ColorOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="red" type="xsd:int"/>
          <element name="green" type="xsd:int"/>
          <element name="blue" type="xsd:int"/>
        </all>
      </complexType>
      
      <complexType name="StyleOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="symbol" type="xsd:string"/>
          <element name="size" type="xsd:int"/>
          <element name="color" type="types:ColorOverlay"/>
          <element name="outlineColor" type="types:ColorOverlay"/>
          <element name="backgroundColor" type="types:ColorOverlay"/>
          <element name="transparency" type="xsd:int"/>
        </all>
      </complexType>
      
      <complexType name="LabelOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="font" type="xsd:string"/>
          <element name="size" type="xsd:int"/>
          <element name="color" type="types:ColorOverlay"/>
          <element name="outlineColor" type="types:ColorOverlay"/>
          <element name="backgroundColor" type="types:ColorOverlay"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfStyleOverlay">
        <complexContent>
	      <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:StyleOverlay[]"/>
          </restriction>
	    </complexContent>
      </complexType>
      
      <complexType name="ClassOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="name" type="xsd:string"/>
          <element name="copyName" type="xsd:string"/>
          <element name="expression" type="xsd:string"/>
          <element name="label" type="types:LabelOverlay"/>
          <element name="styles" type="types:ArrayOfStyleOverlay"/>
          <element name="minScale" type="xsd:double"/>
          <element name="maxScale" type="xsd:double"/>
        </all>
      </complexType>
      
      <complexType name="MetadataOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="name" type="xsd:string"/>
          <element name="value" type="xsd:string"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfClassOverlay">
        <complexContent>
	      <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:ClassOverlay[]"/>
          </restriction>
	    </complexContent>
      </complexType>
      
      <complexType name="ArrayOfMetadataOverlay">
        <complexContent>
	      <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:MetadataOverlay[]"/>
          </restriction>
	    </complexContent>
      </complexType>
      
      <complexType name="LayerOverlay">
        <all>
          <element name="className" type="xsd:string"/>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="classes" type="types:ArrayOfClassOverlay"/>
          <element name="connection" type="xsd:string"/>
          <element name="connectionType" type="xsd:string"/>
          <element name="copyName" type="xsd:string"/>
          <element name="data" type="xsd:string"/>
          <element name="maxScale" type="xsd:string"/>
          <element name="metadatas" type="types:ArrayOfMetadataOverlay"/>
          <element name="minScale" type="xsd:string"/>
          <element name="name" type="xsd:string"/>
          <element name="transparency" type="xsd:int"/>
          <element name="type" type="xsd:string"/>
        </all>
      </complexType>
      
      <complexType name="ArrayOfLayerOverlay">
        <complexContent>
	      <restriction base="enc11:Array">
            <attribute ref="enc11:arrayType" wsdl:arrayType="types:LayerOverlay[]"/>
          </restriction>
	    </complexContent>
      </complexType>
      
      <complexType name="MapOverlayRequest">
        <all>
          <element name="action" type="xsd:int"/>
          <element name="id" type="xsd:string"/>
          <element name="index" type="xsd:int"/>
          <element name="copyIndex" type="xsd:int"/>
          <element name="position" type="types:PositionOverlay"/>
          <element name="className" type="xsd:string"/>
          <element name="layers" type="types:ArrayOfLayerOverlay"/>
        </all>
      </complexType>
